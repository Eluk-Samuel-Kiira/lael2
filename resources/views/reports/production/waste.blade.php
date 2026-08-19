{{-- resources/views/reports/production/waste.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_waste_report'))

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
                                {{ __('pagination.production_waste_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_waste_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paginatedWaste->count() > 0)
                            <button class="btn btn-sm btn-success" onclick="exportWaste()">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
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
                                <form method="GET" action="{{ route('reports.production.waste') }}" id="filterForm">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                            <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                            <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                            <select class="form-select" name="location_id" data-control="select2">
                                                <option value="">{{ __('pagination.all_locations') }}</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                            <select class="form-select" name="status" data-control="select2">
                                                @foreach($statuses as $s)
                                                    <option value="{{ $s['value'] }}" {{ $status == $s['value'] ? 'selected' : '' }}>
                                                        {{ $s['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.waste_type') }}</label>
                                            <select class="form-select" name="waste_type" data-control="select2">
                                                @foreach($wasteTypes as $w)
                                                    <option value="{{ $w['value'] }}" {{ $wasteType == $w['value'] ? 'selected' : '' }}>
                                                        {{ $w['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.variant') }}</label>
                                            <select class="form-select" name="variant_id" data-control="select2">
                                                <option value="">{{ __('pagination.all_variants') }}</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" {{ $variantId == $variant->id ? 'selected' : '' }}>
                                                        {{ $variant->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                    {{ __('pagination.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.production.waste') }}" class="btn btn-light">
                                                    <i class="ki-duotone ki-cross fs-2 me-1"></i>
                                                    {{ __('pagination.clear') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Waste Summary Cards --}}
                <div class="row g-6 mb-6">
                    {{-- Total Waste Rate --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $wasteSummary['total_waste_rate'] <= 5 ? 'success' : ($wasteSummary['total_waste_rate'] <= 15 ? 'warning' : 'danger') }} border border-{{ $wasteSummary['total_waste_rate'] <= 5 ? 'success' : ($wasteSummary['total_waste_rate'] <= 15 ? 'warning' : 'danger') }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-trash fs-2tx text-{{ $wasteSummary['total_waste_rate'] <= 5 ? 'success' : ($wasteSummary['total_waste_rate'] <= 15 ? 'warning' : 'danger') }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $wasteSummary['total_waste_rate'] <= 5 ? 'success' : ($wasteSummary['total_waste_rate'] <= 15 ? 'warning' : 'danger') }}">
                                    {{ number_format($wasteSummary['total_waste_rate'], 1) }}%
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_waste_rate') }}</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($wasteSummary['total_waste'], 1) }} {{ __('pagination.units_wasted') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Input Waste --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-enter fs-2tx text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-warning">{{ number_format($wasteSummary['input_waste_rate'], 1) }}%</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.input_waste_rate') }}</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($wasteSummary['total_input_waste'], 1) }} / {{ number_format($wasteSummary['total_input_quantity'], 1) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Output Defective --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-exit fs-2tx text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-danger">{{ number_format($wasteSummary['output_defective_rate'], 1) }}%</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.defective_rate') }}</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($wasteSummary['total_defective'], 1) }} / {{ number_format($wasteSummary['total_output_quantity'], 1) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Waste Cost --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $wasteSummary['waste_cost_percentage'] <= 5 ? 'success' : ($wasteSummary['waste_cost_percentage'] <= 15 ? 'warning' : 'danger') }} border border-{{ $wasteSummary['waste_cost_percentage'] <= 5 ? 'success' : ($wasteSummary['waste_cost_percentage'] <= 15 ? 'warning' : 'danger') }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-{{ $wasteSummary['waste_cost_percentage'] <= 5 ? 'success' : ($wasteSummary['waste_cost_percentage'] <= 15 ? 'warning' : 'danger') }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $wasteSummary['waste_cost_percentage'] <= 5 ? 'success' : ($wasteSummary['waste_cost_percentage'] <= 15 ? 'warning' : 'danger') }}">
                                    {{ currency_symbol() }}{{ number_format($wasteSummary['waste_cost'], 2) }}
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.waste_cost') }}</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($wasteSummary['waste_cost_percentage'], 1) }}% {{ __('pagination.of_total_cost') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Metrics --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.good_output') }}</span>
                                    <div class="fs-2 fw-bold text-success">{{ number_format($wasteSummary['good_output'], 1) }}</div>
                                </div>
                                <i class="ki-duotone ki-check-circle fs-2tx text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.good_rate') }}</span>
                                    <div class="fs-2 fw-bold text-primary">{{ number_format($wasteSummary['good_rate'], 1) }}%</div>
                                </div>
                                <i class="ki-duotone ki-chart-line fs-2tx text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.total_orders') }}</span>
                                    <div class="fs-2 fw-bold">{{ $wasteSummary['total_orders'] }}</div>
                                </div>
                                <i class="ki-duotone ki-box fs-2tx text-secondary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.completed_orders') }}</span>
                                    <div class="fs-2 fw-bold text-info">{{ $wasteSummary['completed_orders'] }}</div>
                                </div>
                                <i class="ki-duotone ki-check-square fs-2tx text-info">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Severity Distribution --}}
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.waste_severity_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $severityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                                        $severityLabels = [
                                            'low' => __('pagination.low_waste'),
                                            'medium' => __('pagination.medium_waste'),
                                            'high' => __('pagination.high_waste'),
                                            'critical' => __('pagination.critical_waste')
                                        ];
                                    @endphp
                                    @foreach($severityDistribution as $severity => $count)
                                    <div class="col-md-3">
                                        <div class="card card-flush bg-light-{{ $severityColors[$severity] }} border border-{{ $severityColors[$severity] }} border-dashed">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="text-muted">{{ $severityLabels[$severity] ?? ucfirst($severity) }}</span>
                                                    <div class="fs-2 fw-bold text-{{ $severityColors[$severity] }}">{{ $count }}</div>
                                                </div>
                                                <span class="badge badge-light-{{ $severityColors[$severity] }} fs-6">
                                                    {{ $wasteSummary['total_orders'] > 0 ? number_format(($count / $wasteSummary['total_orders']) * 100, 1) : 0 }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts Section --}}
                <div class="row g-6 mb-6">
                    {{-- Monthly Waste Trends --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_waste_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="wasteTrendChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Category Waste --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.waste_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="categoryWasteChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Product Waste Summary --}}
                @if($productWasteSummary->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-product fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_products_by_waste') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.category') }}</th>
                                                <th class="text-center">{{ __('pagination.input_waste') }}</th>
                                                <th class="text-center">{{ __('pagination.defective') }}</th>
                                                <th class="text-center">{{ __('pagination.total_waste') }}</th>
                                                <th class="text-center">{{ __('pagination.waste_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.orders') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($productWasteSummary as $index => $product)
                                            @php
                                                $rateColor = $product->total_rate <= 5 ? 'success' : ($product->total_rate <= 15 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $product->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $product->variant_sku }}</div>
                                                </td>
                                                <td><span class="badge badge-light-primary">{{ $product->category }}</span></td>
                                                <td class="text-center text-warning">{{ number_format($product->input_waste, 1) }}</td>
                                                <td class="text-center text-danger">{{ number_format($product->output_waste, 1) }}</td>
                                                <td class="text-center fw-bold">{{ number_format($product->total_waste, 1) }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $rateColor }}">
                                                        {{ number_format($product->total_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">{{ $product->order_count }}</td>
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

                {{-- Waste by Order Table --}}
                @if($paginatedWaste->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.waste_by_order') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedWaste->count() }} {{ __('pagination.of') }} {{ $paginatedWaste->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.order') }}</th>
                                                <th>{{ __('pagination.status') }}</th>
                                                <th>{{ __('pagination.location') }}</th>
                                                <th class="text-center">{{ __('pagination.input_waste') }}</th>
                                                <th class="text-center">{{ __('pagination.input_waste_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.defective') }}</th>
                                                <th class="text-center">{{ __('pagination.defective_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.total_waste') }}</th>
                                                <th class="text-center">{{ __('pagination.total_waste_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.waste_cost') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedWaste as $index => $item)
                                            @php
                                                $totalRateColor = $item->total_waste_rate <= 5 ? 'success' : ($item->total_waste_rate <= 15 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td>{{ $paginatedWaste->firstItem() + $index }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->production_number }}</div>
                                                    <div class="text-muted fs-8">{{ $item->created_at->format('Y-m-d') }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $item->status_badge }}">
                                                        {{ $item->status }}
                                                    </span>
                                                </td>
                                                <td>{{ $item->location }}</td>
                                                <td class="text-center text-warning">{{ number_format($item->input_waste, 1) }}</td>
                                                <td class="text-center">{{ number_format($item->input_waste_rate, 1) }}%</td>
                                                <td class="text-center text-danger">{{ number_format($item->defective, 1) }}</td>
                                                <td class="text-center">{{ number_format($item->defective_rate, 1) }}%</td>
                                                <td class="text-center fw-bold">{{ number_format($item->total_waste, 1) }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $totalRateColor }}">
                                                        {{ number_format($item->total_waste_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($item->waste_cost, 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="11" class="text-end fw-bold">
                                                    {{ __('pagination.total_orders') }}: {{ $paginatedWaste->total() }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedWaste,
                                        'pageName' => 'page',
                                        'perPageName' => 'per_page',
                                        'showPerPage' => true
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center py-10">
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.no_production_data') }}</p>
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Monthly Waste Trends ──────────────────────────────────────
    const monthlyData = @json($monthlyWaste);
    
    if (monthlyData.length > 0) {
        const months = monthlyData.map(d => d.month);
        const inputWaste = monthlyData.map(d => d.input_waste_rate);
        const defective = monthlyData.map(d => d.defective_rate);
        const totalWaste = monthlyData.map(d => d.total_rate);
        
        const trendChart = new ApexCharts(document.querySelector("#wasteTrendChart"), {
            series: [
                {
                    name: 'Input Waste Rate',
                    data: inputWaste,
                    type: 'line'
                },
                {
                    name: 'Defective Rate',
                    data: defective,
                    type: 'line'
                },
                {
                    name: 'Total Waste Rate',
                    data: totalWaste,
                    type: 'line'
                }
            ],
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            stroke: { width: [3, 3, 4], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Percentage (%)' },
                labels: {
                    formatter: function(val) {
                        return val + '%';
                    }
                }
            },
            colors: ['#FFC700', '#F1416C', '#3E97FF'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return val.toFixed(1) + '%';
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center'
            }
        });
        trendChart.render();
    }
    
    // ─── Category Waste Chart ──────────────────────────────────────
    const categoryData = @json($categoryWasteSummary);
    
    if (categoryData.length > 0) {
        const categories = categoryData.map(d => d.category);
        const inputWaste = categoryData.map(d => d.input_waste_rate);
        const defective = categoryData.map(d => d.defective_rate);
        const totalWaste = categoryData.map(d => d.total_rate);
        
        const categoryChart = new ApexCharts(document.querySelector("#categoryWasteChart"), {
            series: [
                {
                    name: 'Input Waste',
                    data: inputWaste,
                    type: 'bar'
                },
                {
                    name: 'Defective',
                    data: defective,
                    type: 'bar'
                },
                {
                    name: 'Total Rate',
                    data: totalWaste,
                    type: 'line'
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: true },
                stacked: false
            },
            plotOptions: {
                bar: { horizontal: true, columnWidth: '50%' }
            },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Percentage (%)' },
                labels: {
                    formatter: function(val) {
                        return val + '%';
                    }
                }
            },
            colors: ['#FFC700', '#F1416C', '#3E97FF'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 2) {
                            return val.toFixed(1) + '%';
                        }
                        return val.toFixed(1) + '%';
                    }
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            }
        });
        categoryChart.render();
    }
});

// ─── Export Function ──────────────────────────────────────────────
function exportWaste() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.location.href = `/reports/production/waste/export?${params.toString()}`;
}
</script>
@endpush

@endsection