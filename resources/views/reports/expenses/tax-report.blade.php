{{-- resources/views/reports/expenses/tax-report.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.tax_report'))

@section('content')
@if (tenant_can('advanced_reports'))
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('accounting.tax_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('accounting.tax_analysis') }}</li>
                            </ul>
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
                                <form method="GET" action="{{ route('reports.expenses.tax-report') }}" id="filterForm">

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

                                    {{-- Row 2: Category + Tax type + Location + Actions --}}
                                    <div class="row g-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="category_id" data-control="select2">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}"
                                                                {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}@if($category->code) ({{ $category->code }})@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.tax_type') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="tax_type" data-control="select2">
                                                    <option value="all"         {{ $taxType === 'all'         || !$taxType ? 'selected' : '' }}>{{ __('accounting.all_tax_types') }}</option>
                                                    <option value="taxable"     {{ $taxType === 'taxable'     ? 'selected' : '' }}>{{ __('accounting.taxable_only') }}</option>
                                                    <option value="non-taxable" {{ $taxType === 'non-taxable' ? 'selected' : '' }}>{{ __('accounting.non_taxable_only') }}</option>
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

                                        <div class="col-xl-3 d-flex align-items-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.expenses.tax-report') }}"
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
                                        $categoryId
                                            ? ['label' => __('accounting.category'), 'value' => $categories->firstWhere('id', $categoryId)?->name]
                                            : null,
                                        ($taxType && $taxType !== 'all')
                                            ? ['label' => __('accounting.tax_type'), 'value' => __($taxType === 'taxable' ? 'accounting.taxable_only' : 'accounting.non_taxable_only')]
                                            : null,
                                        $locationId
                                            ? ['label' => __('accounting.location'), 'value' => $locations->firstWhere('id', $locationId)?->name]
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
                                        <a href="{{ route('reports.expenses.tax-report') }}"
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

                @if($taxSummary['total_expenses'] > 0)
                    @php
                        $sym = currency_symbol();
                        $stats = [
                            ['color' => 'primary', 'icon' => 'ki-receipt',       'label' => 'total_expenses',    'value' => number_format($taxSummary['total_expenses']),              'money' => false],
                            ['color' => 'success', 'icon' => 'ki-dollar',        'label' => 'gross_amount',      'value' => $taxSummary['total_gross'] ?? 0,                           'money' => true],
                            ['color' => 'warning', 'icon' => 'ki-receipt-tax',   'label' => 'total_tax',         'value' => $taxSummary['total_tax'] ?? 0,                             'money' => true],
                            ['color' => 'info',    'icon' => 'ki-percentage',    'label' => 'average_tax_rate',  'value' => number_format($taxSummary['avg_tax_rate'] ?? 0, 2) . '%',  'money' => false],
                        ];
                    @endphp

                    <div class="row g-6 mb-6">
                        @foreach($stats as $stat)
                            <div class="col-md-6 col-lg-3">
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
                                                    {{ $sym }} {{ number_format($stat['value'], 2) }}
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
                @endif

                {{-- Tax by Category Table --}}
                @if($taxByCategory->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.tax_by_category') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.category') }}</th>
                                        <th class="text-center">{{ __('accounting.expense_count') }}</th>
                                        <th class="text-end">{{ __('accounting.gross_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.tax_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.net_amount') }}</th>
                                        <th class="text-center">{{ __('accounting.avg_tax_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($taxByCategory as $category)
                                    <tr>
                                        <td>{{ $category->category_name }}</td>
                                        <td class="text-center">{{ $category->expense_count }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($category->gross_amount, 2) }}</td>
                                        <td class="text-end text-warning">{{ currency_symbol() }}{{ number_format($category->tax_amount, 2) }}</td>
                                        <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($category->net_amount, 2) }}</td>
                                        <td class="text-center">{{ number_format($category->avg_tax_rate, 2) }}%</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td>{{ __('accounting.total') }}</td>
                                        <td class="text-center">{{ $taxByCategory->sum('expense_count') }}</td>
                                        <td class="text-end">{{ currency_symbol() }} {{ number_format($taxByCategory->sum('gross_amount'), 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }} {{ number_format($taxByCategory->sum('tax_amount'), 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }} {{ number_format($taxByCategory->sum('net_amount'), 2) }}</td>
                                        <td class="text-center">
                                            @php
                                                $totalGrossFooter = $taxByCategory->sum('gross_amount');
                                                $totalTaxFooter   = $taxByCategory->sum('tax_amount');
                                            @endphp
                                            {{ $totalGrossFooter > 0 ? number_format(($totalTaxFooter / $totalGrossFooter) * 100, 2) : 0 }}%
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Monthly Tax Breakdown --}}
                @if($monthlyTax->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.monthly_tax_breakdown') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.period') }}</th>
                                        <th class="text-center">{{ __('accounting.expense_count') }}</th>
                                        <th class="text-end">{{ __('accounting.gross_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.tax_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.net_amount') }}</th>
                                        <th class="text-center">{{ __('accounting.avg_tax_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthlyTax as $month)
                                    <tr>
                                        <td>{{ $month->month_name }}</td>
                                        <td class="text-center">{{ $month->expense_count }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($month->gross_amount, 2) }}</td>
                                        <td class="text-end text-warning">{{ currency_symbol() }}{{ number_format($month->tax_amount, 2) }}</td>
                                        <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($month->net_amount, 2) }}</td>
                                        <td class="text-center">{{ number_format($month->avg_tax_rate, 2) }}%</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Tax Rate Distribution --}}
                @if($taxRateDistribution->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.tax_rate_distribution') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">{{ __('accounting.tax_rate') }}</th>
                                        <th class="text-center">{{ __('accounting.expense_count') }}</th>
                                        <th class="text-end">{{ __('accounting.total_tax') }}</th>
                                        <th class="text-center">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalCount = $taxRateDistribution->sum('expense_count'); @endphp
                                    @foreach($taxRateDistribution as $distribution)
                                    <tr>
                                        <td class="text-center fw-bold">{{ $distribution->tax_rate_percent }}%</td>
                                        <td class="text-center">{{ $distribution->expense_count }}</td>
                                        <td class="text-end text-warning">{{ currency_symbol() }}{{ number_format($distribution->total_tax, 2) }}</td>
                                        <td class="text-center">{{ $totalCount > 0 ? round(($distribution->expense_count / $totalCount) * 100, 1) : 0 }}%</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td class="text-center">{{ __('accounting.total') }}</td>
                                        <td class="text-center">{{ $totalCount }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($taxRateDistribution->sum('total_tax'), 2) }}</td>
                                        <td class="text-center">
                                            @php
                                                $sumPercent = $taxRateDistribution->sum(fn($d) => $totalCount > 0 ? ($d->expense_count / $totalCount) * 100 : 0);
                                            @endphp
                                            {{ number_format($sumPercent, 1) }}%
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- No Data Message --}}
                @if($taxSummary['total_expenses'] == 0)
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('accounting.no_tax_data_found') }}</p>
                        </div>
                    </div>
                </div>
                @endif
                
            </div>
        </div>
    </div>
</div>
@endif
@endsection