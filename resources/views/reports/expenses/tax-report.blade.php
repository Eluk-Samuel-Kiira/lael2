{{-- resources/views/reports/expenses/tax-report.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.tax_report'))

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

                {{-- Filter Section --}}
                <div class="card mb-6">
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.expenses.tax-report') }}" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.category') }}</label>
                                <select class="form-select" name="category_id">
                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.tax_type') }}</label>
                                <select class="form-select" name="tax_type">
                                    <option value="all" {{ $taxType == 'all' ? 'selected' : '' }}>{{ __('accounting.all_tax_types') }}</option>
                                    <option value="taxable" {{ $taxType == 'taxable' ? 'selected' : '' }}>{{ __('accounting.taxable_only') }}</option>
                                    <option value="non-taxable" {{ $taxType == 'non-taxable' ? 'selected' : '' }}>{{ __('accounting.non_taxable_only') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">{{ __('accounting.apply_filters') }}</button>
                                <a href="{{ route('reports.expenses.tax-report') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Tax Summary Cards --}}
                @if($taxSummary['total_expenses'] > 0)
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-primary">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ $taxSummary['total_expenses'] }}</div>
                                <div class="text-muted">{{ __('accounting.total_expenses') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-success">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ currency_symbol() }}{{ number_format($taxSummary['total_gross'] ?? 0, 2) }}</div>
                                <div class="text-muted">{{ __('accounting.gross_amount') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-warning">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ currency_symbol() }}{{ number_format($taxSummary['total_tax'] ?? 0, 2) }}</div>
                                <div class="text-muted">{{ __('accounting.total_tax') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-info">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ number_format($taxSummary['avg_tax_rate'] ?? 0, 2) }}%</div>
                                <div class="text-muted">{{ __('accounting.average_tax_rate') }}</div>
                            </div>
                        </div>
                    </div>
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
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($taxByCategory->sum('gross_amount'), 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($taxByCategory->sum('tax_amount'), 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($taxByCategory->sum('net_amount'), 2) }}</td>
                                        <td></td>
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
                                        <td class="text-center">100%</td>
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
@endsection