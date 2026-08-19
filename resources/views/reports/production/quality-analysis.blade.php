{{-- resources/views/reports/production/quality-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_quality_analysis'))

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
                                {{ __('pagination.production_quality_analysis') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_quality_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paginatedQuality->count() > 0)
                            <button class="btn btn-sm btn-success" onclick="exportQuality()">
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
                                <form method="GET" action="{{ route('reports.production.quality-analysis') }}" id="filterForm">
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
                                            <label class="form-label fw-semibold">{{ __('pagination.quality_status') }}</label>
                                            <select class="form-select" name="quality_status" data-control="select2">
                                                @foreach($qualityStatuses as $q)
                                                    <option value="{{ $q['value'] }}" {{ $qualityStatus == $q['value'] ? 'selected' : '' }}>
                                                        {{ $q['label'] }}
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
                                                <a href="{{ route('reports.production.quality-analysis') }}" class="btn btn-light">
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

                {{-- Quality Score Card --}}
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card card-flush bg-light-{{ $qualitySummary['quality_color'] }} border border-{{ $qualitySummary['quality_color'] }} border-dashed">
                            <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between py-6">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-80px me-4">
                                        <span class="symbol-label bg-{{ $qualitySummary['quality_color'] }} text-white fs-1 fw-bold">
                                            {{ number_format($qualitySummary['overall_quality_score'], 1) }}%
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="fw-bold mb-1">{{ __('pagination.overall_quality_score') }}</h3>
                                        <div class="text-muted">
                                            {{ $qualitySummary['quality_rating'] }} - 
                                            {{ $qualitySummary['total_orders'] }} {{ __('pagination.orders') }} analyzed
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-4 mt-3 mt-md-0">
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-success">{{ number_format($qualitySummary['input_acceptance_rate'], 1) }}%</div>
                                        <span class="text-muted">{{ __('pagination.input_acceptance') }}</span>
                                    </div>
                                    <div class="vr d-none d-md-block"></div>
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-info">{{ number_format($qualitySummary['output_approval_rate'], 1) }}%</div>
                                        <span class="text-muted">{{ __('pagination.output_approval') }}</span>
                                    </div>
                                    <div class="vr d-none d-md-block"></div>
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-danger">{{ number_format($qualitySummary['defective_rate'], 1) }}%</div>
                                        <span class="text-muted">{{ __('pagination.defective_rate') }}</span>
                                    </div>
                                    <div class="vr d-none d-md-block"></div>
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-warning">{{ number_format($qualitySummary['waste_rate'], 1) }}%</div>
                                        <span class="text-muted">{{ __('pagination.waste_rate') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quality Summary Cards --}}
                <div class="row g-6 mb-6">
                    {{-- Input Quality --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-enter fs-2tx text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-4 fw-bold text-success">{{ $qualitySummary['accepted_inputs'] }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.accepted') }}</span>
                                    </div>
                                    <div>
                                        <span class="fs-4 fw-bold text-danger">{{ $qualitySummary['rejected_inputs'] }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.rejected') }}</span>
                                    </div>
                                    <div>
                                        <span class="fs-4 fw-bold text-secondary">{{ $qualitySummary['pending_inputs'] }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.pending') }}</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">{{ __('pagination.input_quality') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Output Quality --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-exit fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-4 fw-bold text-success">{{ $qualitySummary['approved_outputs'] }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.approved') }}</span>
                                    </div>
                                    <div>
                                        <span class="fs-4 fw-bold text-danger">{{ $qualitySummary['rejected_outputs'] }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.rejected') }}</span>
                                    </div>
                                    <div>
                                        <span class="fs-4 fw-bold text-secondary">{{ $qualitySummary['pending_outputs'] }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.pending') }}</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">{{ __('pagination.output_quality') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Defective & Waste --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-trash fs-2tx text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-4 fw-bold text-danger">{{ number_format($qualitySummary['total_defective'], 1) }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.defective') }}</span>
                                    </div>
                                    <div>
                                        <span class="fs-4 fw-bold text-warning">{{ number_format($qualitySummary['total_input_waste'], 1) }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.waste') }}</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">{{ __('pagination.total_loss') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Quality Scores --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-line fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-4 fw-bold text-success">{{ number_format($qualitySummary['input_acceptance_rate'], 1) }}%</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.input_acceptance') }}</span>
                                    </div>
                                    <div>
                                        <span class="fs-4 fw-bold text-info">{{ number_format($qualitySummary['output_approval_rate'], 1) }}%</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.output_approval') }}</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">{{ __('pagination.quality_rates') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts Section --}}
                <div class="row g-6 mb-6">
                    {{-- Quality Trends --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.quality_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="qualityTrendChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Category Quality --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.quality_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="categoryQualityChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Product Quality Summary --}}
                @if($productQualitySummary->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-product fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_products_by_quality') }}</h3>
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
                                                <th class="text-center">{{ __('pagination.total_quantity') }}</th>
                                                <th class="text-center">{{ __('pagination.defective') }}</th>
                                                <th class="text-center">{{ __('pagination.defective_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.quality_score') }}</th>
                                                <th class="text-center">{{ __('pagination.orders') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($productQualitySummary as $index => $product)
                                            @php
                                                $scoreColor = $product->quality_score >= 90 ? 'success' : ($product->quality_score >= 75 ? 'info' : ($product->quality_score >= 60 ? 'warning' : 'danger'));
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $product->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $product->variant_sku }}</div>
                                                </td>
                                                <td><span class="badge badge-light-primary">{{ $product->category }}</span></td>
                                                <td class="text-center">{{ number_format($product->total_quantity, 1) }}</td>
                                                <td class="text-center text-danger">{{ number_format($product->defective_quantity, 1) }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $product->defective_rate <= 5 ? 'success' : ($product->defective_rate <= 15 ? 'warning' : 'danger') }}">
                                                        {{ number_format($product->defective_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $scoreColor }}">
                                                        {{ number_format($product->quality_score, 1) }}%
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

                {{-- Quality by Order Table --}}
                @if($paginatedQuality->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.quality_by_order') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedQuality->count() }} {{ __('pagination.of') }} {{ $paginatedQuality->total() }}
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
                                                <th class="text-center">{{ __('pagination.input_acceptance') }}</th>
                                                <th class="text-center">{{ __('pagination.output_approval') }}</th>
                                                <th class="text-center">{{ __('pagination.defective_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.waste_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.quality_score') }}</th>
                                                <th class="text-center">{{ __('pagination.rating') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedQuality as $index => $item)
                                            <tr>
                                                <td>{{ $paginatedQuality->firstItem() + $index }}</td>
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
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $item->input_acceptance_rate >= 90 ? 'success' : ($item->input_acceptance_rate >= 70 ? 'warning' : 'danger') }}">
                                                        {{ number_format($item->input_acceptance_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $item->output_approval_rate >= 90 ? 'success' : ($item->output_approval_rate >= 70 ? 'warning' : 'danger') }}">
                                                        {{ number_format($item->output_approval_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $item->defective_rate <= 5 ? 'success' : ($item->defective_rate <= 15 ? 'warning' : 'danger') }}">
                                                        {{ number_format($item->defective_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $item->waste_rate <= 5 ? 'success' : ($item->waste_rate <= 15 ? 'warning' : 'danger') }}">
                                                        {{ number_format($item->waste_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $item->quality_color }}">
                                                        {{ number_format($item->overall_quality_score, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $item->quality_color }}">
                                                        {{ $item->quality_rating }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="10" class="text-end fw-bold">
                                                    {{ __('pagination.total_orders') }}: {{ $paginatedQuality->total() }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedQuality,
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
    // ─── Quality Trends Chart ──────────────────────────────────────
    const monthlyData = @json($monthlyQuality);
    
    if (monthlyData.length > 0) {
        const months = monthlyData.map(d => d.month);
        const inputAcceptance = monthlyData.map(d => d.input_acceptance);
        const outputApproval = monthlyData.map(d => d.output_approval);
        const overallScore = monthlyData.map(d => d.overall_score);
        
        const trendChart = new ApexCharts(document.querySelector("#qualityTrendChart"), {
            series: [
                {
                    name: 'Input Acceptance',
                    data: inputAcceptance,
                    type: 'line'
                },
                {
                    name: 'Output Approval',
                    data: outputApproval,
                    type: 'line'
                },
                {
                    name: 'Overall Score',
                    data: overallScore,
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
                min: 0,
                max: 100,
                labels: {
                    formatter: function(val) {
                        return val + '%';
                    }
                }
            },
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
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
    
    // ─── Category Quality Chart ──────────────────────────────────────
    const categoryData = @json($categoryQualitySummary);
    
    if (categoryData.length > 0) {
        const categories = categoryData.map(d => d.category);
        const defectiveRate = categoryData.map(d => d.defective_rate);
        const approvalRate = categoryData.map(d => d.approval_rate);
        const qualityScore = categoryData.map(d => d.quality_score);
        
        const categoryChart = new ApexCharts(document.querySelector("#categoryQualityChart"), {
            series: [
                {
                    name: 'Defective Rate',
                    data: defectiveRate,
                    type: 'bar'
                },
                {
                    name: 'Approval Rate',
                    data: approvalRate,
                    type: 'bar'
                },
                {
                    name: 'Quality Score',
                    data: qualityScore,
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
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
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
                position: 'bottom',
                horizontalAlign: 'center'
            }
        });
        categoryChart.render();
    }
});

// ─── Export Function ──────────────────────────────────────────────
function exportQuality() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.location.href = `/reports/production/quality-analysis/export?${params.toString()}`;
}
</script>
@endpush

@endsection