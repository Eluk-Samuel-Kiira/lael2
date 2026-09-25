{{-- resources/views/reports/expenses/by-vendor.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.expenses_by_vendor'))

@section('content')
@if (tenant_can('advanced_reports'))
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <!-- Left side - Title and Breadcrumb -->
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
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

                        <!-- Right side - Actions -->
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($vendorBreakdown->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
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

                {{-- ═══════════════════════════════════════════════════════════
                    FILTERS
                ═══════════════════════════════════════════════════════════ --}}
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
                                <div class="card-toolbar">
                                    <span class="badge badge-light-info fs-7">
                                        <i class="ki-duotone ki-calendar-8 fs-4 me-1"></i>
                                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                                        &mdash;
                                        {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.expenses.by-vendor') }}" id="filterForm">

                                    {{-- Row 1: Date range --}}
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-12">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control"
                                                        name="start_date" value="{{ $startDate }}" required>
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-1">
                                                    {{ __('accounting.to') }}
                                                </span>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control"
                                                        name="end_date" value="{{ $endDate }}" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Row 2: Vendor + Category + Payment method + Location --}}
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.vendor_name') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-shop fs-2"></i>
                                                </span>
                                                <input type="text" class="form-control"
                                                    name="vendor_name"
                                                    value="{{ $vendorName }}"
                                                    placeholder="{{ __('accounting.search_vendor') }}"
                                                    maxlength="200">
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="category_id" data-control="select2">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($expenseCategories as $category)
                                                        <option value="{{ $category->id }}"
                                                                {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.payment_method') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="payment_method_id" data-control="select2">
                                                    <option value="">{{ __('accounting.all_payment_methods') }}</option>
                                                    @foreach($paymentMethods as $method)
                                                        <option value="{{ $method->id }}"
                                                                {{ (string) $paymentMethodId === (string) $method->id ? 'selected' : '' }}>
                                                            {{ $method->name }}@if($method->is_default) ({{ __('accounting.default') }})@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.location') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="location_id" data-control="select2">
                                                    <option value="">{{ __('pagination.all_locations') }}</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}"
                                                                {{ (string) $locationId === (string) $location->id ? 'selected' : '' }}>
                                                            {{ $location->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Row 3: Min + Max + Actions --}}
                                    <div class="row g-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.min_amount') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                                <input type="number" class="form-control"
                                                    name="min_amount" value="{{ $minAmount }}"
                                                    placeholder="{{ __('accounting.minimum_amount') }}"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.max_amount') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                                <input type="number" class="form-control"
                                                    name="max_amount" value="{{ $maxAmount }}"
                                                    placeholder="{{ __('accounting.maximum_amount') }}"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>

                                        <div class="col-xl-6 d-flex align-items-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.expenses.by-vendor') }}"
                                                class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.clear') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                {{-- Active filter chips --}}
                                @php
                                    $activeFilters = array_filter([
                                        $vendorName
                                            ? ['label' => __('accounting.vendor_name'), 'value' => $vendorName]
                                            : null,
                                        $categoryId
                                            ? ['label' => __('accounting.category'), 'value' => $categories->firstWhere('id', $categoryId)?->name]
                                            : null,
                                        $paymentMethodId
                                            ? ['label' => __('accounting.payment_method'), 'value' => $paymentMethods->firstWhere('id', $paymentMethodId)?->name]
                                            : null,
                                        $locationId
                                            ? ['label' => __('accounting.location'), 'value' => $locations->firstWhere('id', $locationId)?->name]
                                            : null,
                                        ($minAmount !== null && $minAmount !== '')
                                            ? ['label' => __('accounting.min_amount'), 'value' => currency_symbol() . ' ' . number_format((float) $minAmount, 2)]
                                            : null,
                                        ($maxAmount !== null && $maxAmount !== '')
                                            ? ['label' => __('accounting.max_amount'), 'value' => currency_symbol() . ' ' . number_format((float) $maxAmount, 2)]
                                            : null,
                                    ]);
                                @endphp

                                @if(count($activeFilters) > 0)
                                    <div class="separator separator-dashed my-4"></div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="text-muted fw-semibold me-2">
                                            <i class="ki-duotone ki-filter fs-5 me-1"></i>
                                            {{ __('accounting.active_filters') }}:
                                        </span>
                                        @foreach($activeFilters as $filter)
                                            <span class="badge badge-light-primary fs-7">
                                                <strong>{{ $filter['label'] }}:</strong>&nbsp;{{ $filter['value'] }}
                                            </span>
                                        @endforeach
                                        <a href="{{ route('reports.expenses.by-vendor') }}"
                                        class="text-danger fs-7 text-hover-primary ms-2 d-inline-flex align-items-center gap-1">
                                            <i class="ki-duotone ki-cross fs-5"></i>
                                            {{ __('accounting.clear_all') }}
                                        </a>
                                    </div>
                                @endif
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
                                    @php
                                        $stats = [
                                            ['color' => 'primary',   'icon' => 'ki-shop',         'label' => 'total_vendors',       'value' => $summary['total_vendors'],       'money' => false],
                                            ['color' => 'success',   'icon' => 'ki-receipt',      'label' => 'total_transactions',  'value' => $summary['total_transactions'],  'money' => false],
                                            ['color' => 'info',      'icon' => 'ki-chart-simple', 'label' => 'grand_total',         'value' => $summary['total_amount'],        'money' => true],
                                            ['color' => 'warning',   'icon' => 'ki-receipt-tax',  'label' => 'total_tax',           'value' => $summary['total_tax'],           'money' => true],
                                            ['color' => 'danger',    'icon' => 'ki-calculator',   'label' => 'average_transaction', 'value' => $summary['avg_transaction'],     'money' => true],
                                            ['color' => 'secondary', 'icon' => 'ki-arrow-up',     'label' => 'largest_transaction', 'value' => $summary['largest_single'],      'money' => true],
                                        ];
                                    @endphp

                                    <div class="row g-6">
                                        @foreach($stats as $stat)
                                            <div class="col-md-6 col-lg-2">
                                                <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                                    <div class="card-body d-flex flex-column justify-content-center text-center">
                                                        <div class="mb-4">
                                                            <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </div>
                                                        <div class="mb-1">
                                                            <span class="fs-2 fw-bold text-gray-800">
                                                                @if($stat['money'])
                                                                    {{ currency_symbol() }} {{ number_format($stat['value'], 2) }}
                                                                @else
                                                                    {{ $stat['value'] }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="text-gray-600 fw-semibold fs-7">
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
                                                    <td><span class="text-gray-800 fw-semibold">{{ currency_symbol() }} {{ number_format($vendor->total_amount, 2) }}</span></td>
                                                    <td><span class="text-info">{{ currency_symbol() }} {{ number_format($vendor->total_tax, 2) }}</span></td>
                                                    <td><span class="fw-bold text-success">{{ currency_symbol() }} {{ number_format($vendor->grand_total, 2) }}</span></td>
                                                    <td><span class="text-gray-600">{{ currency_symbol() }} {{ number_format($vendor->average_transaction, 2) }}</span></td>
                                                    <td><span class="text-danger">{{ currency_symbol() }} {{ number_format($vendor->largest_transaction, 2) }}</span></td>
                                                    <td><span class="text-secondary">{{ currency_symbol() }} {{ number_format($vendor->smallest_transaction, 2) }}</span></td>
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
                                        @if(request()->filled('vendor_name') || request()->filled('category_id') || request()->filled('payment_method_id') || request()->filled('location_id') || request()->filled('min_amount') || request()->filled('max_amount'))
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
                                                <td><span class="fw-bold text-success">{{ currency_symbol() }} {{ number_format($topVendor->monthly_total, 2) }}</span></td>
                                                <td><span class="text-gray-600">{{ currency_symbol() }} {{ number_format($topVendor->monthly_average, 2) }}</span></td>
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
        </div>
    </div>
</div>
@endif
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
                title: { text: '{{ __('accounting.amount') }} ({{ currency_symbol() }})' },
                labels: {
                    formatter: function (val) {
                        return '{{ currency_symbol() }}' + val.toLocaleString();
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return '{{ currency_symbol() }}' +
                            val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
    document.getElementById('filterForm')?.addEventListener('submit', function (e) {
        const minEl = document.querySelector('[name="min_amount"]');
        const maxEl = document.querySelector('[name="max_amount"]');
        const minAmount = parseFloat(minEl?.value) || 0;
        const maxAmount = parseFloat(maxEl?.value) || 0;

        if (minAmount > 0 && maxAmount > 0 && minAmount > maxAmount) {
            e.preventDefault();
            toastr.warning('{{ __("accounting.min_amount_cannot_exceed_max") }}');
            minEl?.focus();
            return false;
        }
    });
</script>
@endpush

@endsection