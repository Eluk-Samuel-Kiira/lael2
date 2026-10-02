{{-- resources/views/reports/production/input-output.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_input_output'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('pagination.production_input_output') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('pagination.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_input_output') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paginatedComparison->count() > 0)
                            <button class="btn btn-sm btn-success" onclick="exportInputOutput()">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
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
                                <form method="GET" action="{{ route('reports.production.input-output') }}" id="filterForm">
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
                                            <label class="form-label fw-semibold">{{ __('pagination.comparison_type') }}</label>
                                            <select class="form-select" name="comparison_type">
                                                @foreach($comparisonTypes as $c)
                                                    <option value="{{ $c['value'] }}" {{ $comparisonType == $c['value'] ? 'selected' : '' }}>
                                                        {{ $c['label'] }}
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
                                                <a href="{{ route('reports.production.input-output') }}" class="btn btn-light">
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

                {{-- Summary Cards --}}
                <div class="row g-6 mb-6">
                    {{-- Quantity Comparison --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-arrows-circle fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-5 fw-bold text-danger">
                                            {{ number_format($inputOutputSummary['total_input_kg'], 0) }}
                                        </span>
                                        <span class="text-muted fs-8 d-block">IN (kg)</span>
                                    </div>
                                    <div class="vr"></div>
                                    <div>
                                        <span class="fs-5 fw-bold text-success">
                                            {{ number_format($inputOutputSummary['total_output_kg'], 0) }}
                                        </span>
                                        <span class="text-muted fs-8 d-block">OUT (kg)</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">
                                    Ratio: {{ number_format($inputOutputSummary['quantity_ratio'], 3) }}
                                </span>
                                <span class="text-danger fs-8">
                                    Loss: {{ number_format($inputOutputSummary['loss_kg'], 0) }} kg
                                    ({{ number_format($inputOutputSummary['loss_rate'], 1) }}%)
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Value Comparison --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-5 fw-bold text-danger">
                                            {{ currency_symbol() }} {{ number_format($inputOutputSummary['total_input_cost'], 0) }}
                                        </span>
                                        <span class="text-muted fs-8 d-block">Batch</span>
                                    </div>
                                    <div class="vr"></div>
                                    <div>
                                        <span class="fs-5 fw-bold text-success">
                                            {{ currency_symbol() }} {{ number_format($inputOutputSummary['total_revenue'], 0) }}
                                        </span>
                                        <span class="text-muted fs-8 d-block">Revenue</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">
                                    Ratio: {{ number_format($inputOutputSummary['cost_ratio'], 3) }}
                                </span>
                                <span class="text-{{ $inputOutputSummary['net_value'] >= 0 ? 'success' : 'danger' }} fs-8">
                                    Net: {{ currency_symbol() }} {{ number_format($inputOutputSummary['net_value'], 0) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Yield --}}
                    @php
                        $yldColor = $inputOutputSummary['quantity_efficiency'] >= 90 ? 'success'
                            : ($inputOutputSummary['quantity_efficiency'] >= 70 ? 'warning' : 'danger');
                    @endphp
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $yldColor }} border border-{{ $yldColor }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-line fs-2tx text-{{ $yldColor }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $yldColor }}">
                                    {{ number_format($inputOutputSummary['quantity_efficiency'], 1) }}%
                                </span>
                                <span class="text-gray-600 fw-semibold">True Yield (kg/kg)</span>
                                <span class="text-muted fs-8">
                                    {{ $inputOutputSummary['quantity_ratio'] >= 1 ? 'Efficient' : 'Lossy' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Cost Efficiency --}}
                    @php
                        $ceColor = $inputOutputSummary['cost_efficiency'] >= 120 ? 'success'
                            : ($inputOutputSummary['cost_efficiency'] >= 100 ? 'warning' : 'danger');
                    @endphp
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $ceColor }} border border-{{ $ceColor }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-pie fs-2tx text-{{ $ceColor }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $ceColor }}">
                                    {{ number_format($inputOutputSummary['cost_efficiency'], 1) }}%
                                </span>
                                <span class="text-gray-600 fw-semibold">Cost Efficiency</span>
                                <span class="text-muted fs-8">
                                    {{ $inputOutputSummary['cost_ratio'] >= 1 ? 'Profitable' : 'Loss-making' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Secondary Metrics --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.total_orders') }}</span>
                                    <div class="fs-2 fw-bold">{{ $inputOutputSummary['total_orders'] }}</div>
                                    <span class="text-muted fs-8">{{ $inputOutputSummary['completed_orders'] }} completed</span>
                                </div>
                                <i class="ki-duotone ki-box fs-2tx text-secondary">
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
                                    <span class="text-muted">Avg In / Order</span>
                                    <div class="fs-2 fw-bold text-primary">
                                        {{ number_format($inputOutputSummary['avg_input_qty_per_order'], 0) }} kg
                                    </div>
                                </div>
                                <i class="ki-duotone ki-enter fs-2tx text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">Avg Out / Order</span>
                                    <div class="fs-2 fw-bold text-success">
                                        {{ number_format($inputOutputSummary['avg_output_qty_per_order'], 0) }} kg
                                    </div>
                                </div>
                                <i class="ki-duotone ki-exit fs-2tx text-success">
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
                                    <span class="text-muted">Revenue / kg Out</span>
                                    <div class="fs-2 fw-bold text-info">
                                        {{ currency_symbol() }} {{ number_format($inputOutputSummary['revenue_per_kg'], 0) }}
                                    </div>
                                    <span class="text-muted fs-8">
                                        Batch cost / kg: {{ currency_symbol() }} {{ number_format($inputOutputSummary['cost_per_kg_input'], 0) }}
                                    </span>
                                </div>
                                <i class="ki-duotone ki-calculator fs-2tx text-info">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts --}}
                <div class="row g-6 mb-6">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_input_output') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.category_comparison') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="categoryChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top Products --}}
                @if($productSummary->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-product fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_products_input_output') }}</h3>
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
                                                <th class="text-center">In (kg)</th>
                                                <th class="text-center">Out (kg)</th>
                                                <th class="text-center">Net (kg)</th>
                                                <th class="text-center">Value Δ</th>
                                                <th class="text-center">{{ __('pagination.type') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($productSummary as $index => $item)
                                            @php
                                                $typeColor = $item->type === 'net_producer' ? 'success'
                                                    : ($item->type === 'net_consumer' ? 'danger' : 'secondary');
                                                $typeIcon = $item->type === 'net_producer' ? 'arrow-up'
                                                    : ($item->type === 'net_consumer' ? 'arrow-down' : 'minus');
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $item->variant_sku }}</div>
                                                </td>
                                                <td><span class="badge badge-light-primary">{{ $item->category }}</span></td>
                                                <td class="text-center text-danger">{{ number_format($item->input_kg, 0) }}</td>
                                                <td class="text-center text-success">{{ number_format($item->output_kg, 0) }}</td>
                                                <td class="text-center fw-bold text-{{ $item->qty_difference >= 0 ? 'success' : 'danger' }}">
                                                    {{ $item->qty_difference >= 0 ? '+' : '' }}{{ number_format($item->qty_difference, 0) }}
                                                </td>
                                                <td class="text-center text-{{ $item->cost_difference >= 0 ? 'success' : 'danger' }}">
                                                    {{ currency_symbol() }} {{ number_format($item->cost_difference, 0) }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $typeColor }}">
                                                        <i class="ki-duotone ki-{{ $typeIcon }} fs-2 me-1"></i>
                                                        {{ __($item->type) }}
                                                    </span>
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

                {{-- Per-Order Comparison --}}
                @if($paginatedComparison->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.input_output_by_order') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedComparison->count() }} {{ __('pagination.of') }} {{ $paginatedComparison->total() }}
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
                                                <th class="text-center">In (kg)</th>
                                                <th class="text-center">Out (kg)</th>
                                                <th class="text-center">Loss (kg)</th>
                                                <th class="text-center">Yield</th>
                                                <th class="text-center">Batch Cost</th>
                                                <th class="text-center">Revenue</th>
                                                <th class="text-center">Net Value</th>
                                                <th class="text-center">Cost Eff.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedComparison as $index => $item)
                                            <tr>
                                                <td>{{ $paginatedComparison->firstItem() + $index }}</td>
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
                                                <td class="text-center text-danger">
                                                    {{ number_format($item->input_quantity, 0) }}
                                                </td>
                                                <td class="text-center text-success">
                                                    {{ number_format($item->output_quantity, 0) }}
                                                </td>
                                                <td class="text-center text-{{ $item->loss_kg > 0 ? 'danger' : 'muted' }}">
                                                    {{ number_format($item->loss_kg, 0) }}
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $yColor = $item->qty_efficiency >= 90 ? 'success'
                                                            : ($item->qty_efficiency >= 70 ? 'warning' : 'danger');
                                                    @endphp
                                                    <span class="badge badge-light-{{ $yColor }}">
                                                        {{ number_format($item->qty_efficiency, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    {{ currency_symbol() }} {{ number_format($item->input_cost, 0) }}
                                                </td>
                                                <td class="text-center">
                                                    {{ currency_symbol() }} {{ number_format($item->output_cost, 0) }}
                                                </td>
                                                <td class="text-center text-{{ $item->cost_difference >= 0 ? 'success' : 'danger' }}">
                                                    {{ currency_symbol() }} {{ number_format($item->cost_difference, 0) }}
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $cColor = $item->cost_efficiency >= 120 ? 'success'
                                                            : ($item->cost_efficiency >= 100 ? 'warning' : 'danger');
                                                    @endphp
                                                    <span class="badge badge-light-{{ $cColor }}">
                                                        {{ number_format($item->cost_efficiency, 1) }}%
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="12" class="text-end fw-bold">
                                                    {{ __('pagination.total_orders') }}: {{ $paginatedComparison->total() }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedComparison,
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
    // ─── Monthly Input vs Output ───────────────────────────────────
    const monthlyData = @json($monthlyComparison);

    if (monthlyData.length > 0) {
        const months   = monthlyData.map(d => d.month);
        const inputKg  = monthlyData.map(d => d.input_quantity);
        const outputKg = monthlyData.map(d => d.output_quantity);
        const yieldPct = monthlyData.map(d => d.yield);

        const monthlyChart = new ApexCharts(document.querySelector("#monthlyChart"), {
            series: [
                { name: 'Input (kg)',  data: inputKg,  type: 'bar'  },
                { name: 'Output (kg)', data: outputKg, type: 'bar'  },
                { name: 'Yield %',     data: yieldPct, type: 'line' }
            ],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: true },
                stacked: false
            },
            plotOptions: {
                bar: { horizontal: false, columnWidth: '35%' }
            },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }
            },
            yaxis: [
                {
                    title: { text: 'Quantity (kg)' },
                    labels: { formatter: v => Number(v).toFixed(0) }
                },
                {
                    opposite: true,
                    title: { text: 'Yield %' },
                    labels: { formatter: v => Number(v).toFixed(0) + '%' }
                }
            ],
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (val, { seriesIndex }) {
                        if (seriesIndex === 2) return Number(val).toFixed(2) + '%';
                        return Number(val).toFixed(1) + ' kg';
                    }
                }
            }
        });
        monthlyChart.render();
    }

    // ─── Category Comparison ───────────────────────────────────────
    const categoryData = @json($categoryComparison);

    if (categoryData.length > 0) {
        const categories = categoryData.map(d => d.category);
        const inputKg    = categoryData.map(d => d.input_kg);
        const outputKg   = categoryData.map(d => d.output_kg);

        const categoryChart = new ApexCharts(document.querySelector("#categoryChart"), {
            series: [
                { name: 'Input (kg)',  data: inputKg,  type: 'bar' },
                { name: 'Output (kg)', data: outputKg, type: 'bar' }
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
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Quantity (kg)' },
                labels: { formatter: v => Number(v).toFixed(0) }
            },
            colors: ['#F1416C', '#50CD89'],
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: v => Number(v).toFixed(1) + ' kg' }
            },
            legend: { position: 'bottom', horizontalAlign: 'center' }
        });
        categoryChart.render();
    }
});

// ─── Export ────────────────────────────────────────────────────────
function exportInputOutput() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    window.location.href = `/reports/production/input-output/export?${params.toString()}`;
}
</script>
@endpush

@endsection