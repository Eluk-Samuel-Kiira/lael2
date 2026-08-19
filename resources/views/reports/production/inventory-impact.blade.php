{{-- resources/views/reports/production/inventory-impact.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_inventory_impact'))

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
                                {{ __('pagination.production_inventory_impact') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_inventory_impact') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paginatedImpact->count() > 0)
                            <button class="btn btn-sm btn-success" onclick="exportInventoryImpact()">
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
                                <form method="GET" action="{{ route('reports.production.inventory-impact') }}" id="filterForm">
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
                                        <div class="col-md-2 d-flex align-items-end">
                                            <div class="d-flex gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                    {{ __('pagination.apply') }}
                                                </button>
                                                <a href="{{ route('reports.production.inventory-impact') }}" class="btn btn-light">
                                                    <i class="ki-duotone ki-cross fs-2"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Impact Summary Cards --}}
                <div class="row g-6 mb-6">
                    {{-- Net Inventory Change --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $impactSummary['net_inventory_change'] >= 0 ? 'success' : 'danger' }} border border-{{ $impactSummary['net_inventory_change'] >= 0 ? 'success' : 'danger' }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-arrows-circle fs-2tx text-{{ $impactSummary['net_inventory_change'] >= 0 ? 'success' : 'danger' }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $impactSummary['net_inventory_change'] >= 0 ? 'success' : 'danger' }}">
                                    {{ $impactSummary['net_inventory_change'] >= 0 ? '+' : '' }}{{ number_format($impactSummary['net_inventory_change'], 1) }}
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.net_inventory_change') }}</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($impactSummary['total_output_quantity'], 1) }} / {{ number_format($impactSummary['total_input_quantity'], 1) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Net Value Change --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $impactSummary['net_value_change'] >= 0 ? 'success' : 'danger' }} border border-{{ $impactSummary['net_value_change'] >= 0 ? 'success' : 'danger' }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-{{ $impactSummary['net_value_change'] >= 0 ? 'success' : 'danger' }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $impactSummary['net_value_change'] >= 0 ? 'success' : 'danger' }}">
                                    {{ currency_symbol() }}{{ number_format($impactSummary['net_value_change'], 2) }}
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.net_value_change') }}</span>
                                <span class="text-muted fs-8">
                                    {{ currency_symbol() }}{{ number_format($impactSummary['total_output_value'], 2) }} / {{ currency_symbol() }}{{ number_format($impactSummary['total_input_value'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Inventory Turnover --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-repeat fs-2tx text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-info">{{ number_format($impactSummary['inventory_turnover'], 2) }}</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.inventory_turnover') }}</span>
                                <span class="text-muted fs-8">
                                    {{ $impactSummary['total_orders'] }} {{ __('pagination.orders') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Products Impacted --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-box fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-primary">{{ $impactSummary['total_consumed_products'] + $impactSummary['total_produced_products'] }}</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.products_impacted') }}</span>
                                <span class="text-muted fs-8">
                                    {{ $impactSummary['total_consumed_products'] }} {{ __('pagination.consumed') }} / {{ $impactSummary['total_produced_products'] }} {{ __('pagination.produced') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Metrics --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.total_defective') }}</span>
                                    <div class="fs-2 fw-bold text-danger">{{ number_format($impactSummary['total_defective'], 1) }}</div>
                                </div>
                                <i class="ki-duotone ki-cross-circle fs-2tx text-danger">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.total_waste') }}</span>
                                    <div class="fs-2 fw-bold text-warning">{{ number_format($impactSummary['total_waste'], 1) }}</div>
                                </div>
                                <i class="ki-duotone ki-trash fs-2tx text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.order_completion') }}</span>
                                    <div class="fs-2 fw-bold text-success">
                                        {{ $impactSummary['completed_orders'] }} / {{ $impactSummary['total_orders'] }}
                                    </div>
                                </div>
                                <i class="ki-duotone ki-check-square fs-2tx text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts Section --}}
                <div class="row g-6 mb-6">
                    {{-- Monthly Inventory Impact --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_inventory_impact') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyImpactChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Category Impact --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.impact_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="categoryImpactChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top Consumed vs Produced --}}
                <div class="row g-6 mb-6">
                    {{-- Top Consumed --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-enter fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_consumed_products') }}</h3>
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
                                                <th class="text-center">{{ __('pagination.quantity') }}</th>
                                                <th class="text-center">{{ __('pagination.cost') }}</th>
                                                <th class="text-center">{{ __('pagination.orders') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topConsumed as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $item->variant_sku }}</div>
                                                </td>
                                                <td><span class="badge badge-light-danger">{{ $item->category }}</span></td>
                                                <td class="text-center text-danger fw-bold">{{ number_format($item->total_quantity, 1) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($item->total_cost, 2) }}</td>
                                                <td class="text-center">{{ $item->order_count }}</td>
                                            </tr>
                                            @endforeach
                                            @if($topConsumed->isEmpty())
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">
                                                    {{ __('pagination.no_data_available') }}
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Top Produced --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-exit fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_produced_products') }}</h3>
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
                                                <th class="text-center">{{ __('pagination.quantity') }}</th>
                                                <th class="text-center">{{ __('pagination.cost') }}</th>
                                                <th class="text-center">{{ __('pagination.orders') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topProduced as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $item->variant_sku }}</div>
                                                </td>
                                                <td><span class="badge badge-light-success">{{ $item->category }}</span></td>
                                                <td class="text-center text-success fw-bold">{{ number_format($item->total_quantity, 1) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($item->total_cost, 2) }}</td>
                                                <td class="text-center">{{ $item->order_count }}</td>
                                            </tr>
                                            @endforeach
                                            @if($topProduced->isEmpty())
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">
                                                    {{ __('pagination.no_data_available') }}
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Net Impact by Product --}}
                @if($netImpactSorted->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-balance fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.net_inventory_impact_by_product') }}</h3>
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
                                                <th class="text-center">{{ __('pagination.consumed') }}</th>
                                                <th class="text-center">{{ __('pagination.produced') }}</th>
                                                <th class="text-center">{{ __('pagination.net_change') }}</th>
                                                <th class="text-center">{{ __('pagination.impact_type') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($netImpactSorted as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $item->variant_sku }}</div>
                                                </td>
                                                <td><span class="badge badge-light-primary">{{ $item->category }}</span></td>
                                                <td class="text-center text-danger">{{ number_format($item->consumed_quantity, 1) }}</td>
                                                <td class="text-center text-success">{{ number_format($item->produced_quantity, 1) }}</td>
                                                <td class="text-center fw-bold text-{{ $item->net_quantity >= 0 ? 'success' : 'danger' }}">
                                                    {{ $item->net_quantity >= 0 ? '+' : '' }}{{ number_format($item->net_quantity, 1) }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $item->impact_color }}">
                                                        @if($item->net_quantity > 0)
                                                            <i class="ki-duotone ki-arrow-up fs-2 me-1"></i>
                                                            {{ __('pagination.net_producer') }}
                                                        @elseif($item->net_quantity < 0)
                                                            <i class="ki-duotone ki-arrow-down fs-2 me-1"></i>
                                                            {{ __('pagination.net_consumer') }}
                                                        @else
                                                            {{ __('pagination.neutral') }}
                                                        @endif
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

                {{-- Impact by Order Table --}}
                @if($paginatedImpact->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.inventory_impact_by_order') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedImpact->count() }} {{ __('pagination.of') }} {{ $paginatedImpact->total() }}
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
                                                <th class="text-center">{{ __('pagination.input_qty') }}</th>
                                                <th class="text-center">{{ __('pagination.output_qty') }}</th>
                                                <th class="text-center">{{ __('pagination.net_change') }}</th>
                                                <th class="text-center">{{ __('pagination.input_cost') }}</th>
                                                <th class="text-center">{{ __('pagination.output_cost') }}</th>
                                                <th class="text-center">{{ __('pagination.defective') }}</th>
                                                <th class="text-center">{{ __('pagination.waste') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedImpact as $index => $item)
                                            <tr>
                                                <td>{{ $paginatedImpact->firstItem() + $index }}</td>
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
                                                <td class="text-center text-danger">{{ number_format($item->input_quantity, 1) }}</td>
                                                <td class="text-center text-success">{{ number_format($item->output_quantity, 1) }}</td>
                                                <td class="text-center fw-bold text-{{ $item->net_quantity >= 0 ? 'success' : 'danger' }}">
                                                    {{ $item->net_quantity >= 0 ? '+' : '' }}{{ number_format($item->net_quantity, 1) }}
                                                </td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($item->input_value, 2) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($item->output_value, 2) }}</td>
                                                <td class="text-center text-danger">{{ number_format($item->defective, 1) }}</td>
                                                <td class="text-center text-warning">{{ number_format($item->waste, 1) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="11" class="text-end fw-bold">
                                                    {{ __('pagination.total_orders') }}: {{ $paginatedImpact->total() }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedImpact,
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
    // ─── Monthly Impact Chart ──────────────────────────────────────
    const monthlyData = @json($monthlyImpact);
    
    if (monthlyData.length > 0) {
        const months = monthlyData.map(d => d.month);
        const inputQty = monthlyData.map(d => d.input_quantity);
        const outputQty = monthlyData.map(d => d.output_quantity);
        const netQty = monthlyData.map(d => d.net_quantity);
        
        const monthlyChart = new ApexCharts(document.querySelector("#monthlyImpactChart"), {
            series: [
                {
                    name: 'Input Quantity',
                    data: inputQty,
                    type: 'bar'
                },
                {
                    name: 'Output Quantity',
                    data: outputQty,
                    type: 'bar'
                },
                {
                    name: 'Net Change',
                    data: netQty,
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
                bar: { horizontal: false, columnWidth: '35%' }
            },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }
            },
            yaxis: [
                { title: { text: 'Quantity' } },
                { 
                    opposite: true, 
                    title: { text: 'Net Change' },
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(1);
                        }
                    }
                }
            ],
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 2) {
                            return (val >= 0 ? '+' : '') + val.toFixed(1);
                        }
                        return val.toFixed(1);
                    }
                }
            }
        });
        monthlyChart.render();
    }
    
    // ─── Category Impact Chart ──────────────────────────────────────
    const categoryData = @json($categoryImpact);
    
    if (categoryData.length > 0) {
        const categories = categoryData.map(d => d.category);
        const consumed = categoryData.map(d => d.consumed_quantity);
        const produced = categoryData.map(d => d.produced_quantity);
        
        const categoryChart = new ApexCharts(document.querySelector("#categoryImpactChart"), {
            series: [
                {
                    name: 'Consumed',
                    data: consumed,
                    type: 'bar'
                },
                {
                    name: 'Produced',
                    data: produced,
                    type: 'bar'
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
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Quantity' }
            },
            colors: ['#F1416C', '#50CD89'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return val.toFixed(1);
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
function exportInventoryImpact() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.location.href = `/reports/production/inventory-impact/export?${params.toString()}`;
}
</script>
@endpush

@endsection