{{-- resources/views/reports/inventory/movement-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.movement_analysis_report'))

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
                                {{ __('pagination.movement_analysis_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.movement_analysis') }}</li>
                            </ul>
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
                                <form method="GET" action="{{ route('reports.inventory.movement-analysis') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap">
                                        {{-- Date Range --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group w-100">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="start_date" 
                                                        value="{{ $startDate }}">
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('pagination.to') }}</span>
                                                <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('pagination.to') }}</span>
                                                <div class="input-group w-100">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="end_date" 
                                                        value="{{ $endDate }}">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Movement Type --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.movement_type') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-chart-line fs-2"></i>
                                                </span>
                                                <select class="form-select" name="movement_type">
                                                    <option value="all" {{ $movementType == 'all' ? 'selected' : '' }}>
                                                        {{ __('pagination.all_types') }}
                                                    </option>
                                                    <option value="fast" {{ $movementType == 'fast' ? 'selected' : '' }}>
                                                        {{ __('pagination.fast_moving') }}
                                                    </option>
                                                    <option value="slow" {{ $movementType == 'slow' ? 'selected' : '' }}>
                                                        {{ __('pagination.slow_moving') }}
                                                    </option>
                                                    <option value="non-moving" {{ $movementType == 'non-moving' ? 'selected' : '' }}>
                                                        {{ __('pagination.non_moving') }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.inventory.movement-analysis') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.clear') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Movement Statistics --}}
                @if($movementData->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.movement_statistics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $stats = [
                                            [
                                                'key' => 'fast_moving', 
                                                'color' => 'success', 
                                                'icon' => 'ki-rocket', 
                                                'label' => 'fast_moving',
                                                'value' => number_format($movementStats['fast_moving']),
                                                'subtitle' => __('pagination.high_demand_items')
                                            ],
                                            [
                                                'key' => 'slow_moving', 
                                                'color' => 'warning', 
                                                'icon' => 'ki-walk', 
                                                'label' => 'slow_moving',
                                                'value' => number_format($movementStats['slow_moving']),
                                                'subtitle' => __('pagination.low_demand_items')
                                            ],
                                            [
                                                'key' => 'non_moving', 
                                                'color' => 'danger', 
                                                'icon' => 'ki-pause-circle', 
                                                'label' => 'non_moving',
                                                'value' => number_format($movementStats['non_moving']),
                                                'subtitle' => __('pagination.no_movement_items')
                                            ],
                                            [
                                                'key' => 'total_items', 
                                                'color' => 'primary', 
                                                'icon' => 'ki-box', 
                                                'label' => 'total_items_analyzed',
                                                'value' => number_format($movementData->total()),
                                                'subtitle' => __('pagination.period') . ': ' . $startDate . ' - ' . $endDate
                                            ]
                                        ];
                                    @endphp
                                    
                                    @foreach($stats as $stat)
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush border border-{{ $stat['color'] }} border-dashed h-100">
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
                                                <div class="text-gray-600 fw-semibold mb-2">
                                                    {{ __('pagination.' . $stat['label']) }}
                                                </div>
                                                <div class="fs-8 text-muted">
                                                    {{ $stat['subtitle'] }}
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

                {{-- Movement Analysis Table --}}
                @if($movementData->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.movement_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $movementData->count() }} {{ __('pagination.of') }} {{ $movementData->total() }} {{ __('pagination.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.total_movement') }}</th>
                                                <th>{{ __('pagination.transactions') }}</th>
                                                <th>{{ __('pagination.avg_daily_movement') }}</th>
                                                <th>{{ __('pagination.first_movement') }}</th>
                                                <th>{{ __('pagination.last_movement') }}</th>
                                                <th>{{ __('pagination.days_since_last') }}</th>
                                                <th>{{ __('pagination.movement_category') }}</th>
                                                <th>{{ __('pagination.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($movementData as $item)
                                            @php
                                                $categoryColor = 'primary';
                                                $categoryIcon = 'ki-check';
                                                
                                                if ($item->movement_category == 'fast') {
                                                    $categoryColor = 'success';
                                                    $categoryIcon = 'ki-rocket';
                                                } elseif ($item->movement_category == 'slow') {
                                                    $categoryColor = 'warning';
                                                    $categoryIcon = 'ki-walk';
                                                } else {
                                                    $categoryColor = 'danger';
                                                    $categoryIcon = 'ki-pause-circle';
                                                }
                                                
                                                $daysSinceColor = 'success';
                                                if ($item->days_since_last_movement > 30) {
                                                    $daysSinceColor = 'warning';
                                                }
                                                if ($item->days_since_last_movement > 90) {
                                                    $daysSinceColor = 'danger';
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold">{{ $item->variant->sku ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->variant->name ?? '-' }}</div>
                                                    <div class="text-muted">{{ $item->variant->product->name ?? '' }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">{{ number_format($item->total_movement) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $item->transaction_count }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($item->avg_daily_movement, 1) }}</span>
                                                    <div class="progress mt-1" style="height: 5px; width: 80px;">
                                                        @php
                                                            $progressWidth = min(100, ($item->avg_daily_movement / 20) * 100);
                                                        @endphp
                                                        <div class="progress-bar bg-{{ $categoryColor }}" 
                                                             style="width: {{ $progressWidth }}%"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ \Carbon\Carbon::parse($item->first_movement)->format('Y-m-d') }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ \Carbon\Carbon::parse($item->last_movement)->format('Y-m-d') }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-{{ $daysSinceColor }}">
                                                        {{ $item->days_since_last_movement }} {{ __('pagination.days') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $categoryColor }}">
                                                        <i class="ki-duotone {{ $categoryIcon }} fs-2 me-1">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        @if($item->movement_category == 'fast')
                                                            {{ __('pagination.fast_moving') }}
                                                        @elseif($item->movement_category == 'slow')
                                                            {{ __('pagination.slow_moving') }}
                                                        @else
                                                            {{ __('pagination.non_moving') }}
                                                        @endif
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-icon btn-light-primary" 
                                                            data-bs-toggle="tooltip" 
                                                            title="{{ __('pagination.view_logs') }}"
                                                            onclick="viewMovementLogs({{ $item->variant_id }}, '{{ $item->variant->name ?? 'Product' }}')">
                                                        <i class="ki-duotone ki-eye fs-2"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($movementData->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $movementData->firstItem() }} - {{ $movementData->lastItem() }} {{ __('pagination.of') }} {{ $movementData->total() }}
                                        </div>
                                        <div>
                                            {{ $movementData->links() }}
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @else
                    {{-- No Data Message --}}
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
                                        <p class="text-muted fs-6">{{ __('pagination.no_movement_data_found') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'movement_type']))
                                        <a href="{{ route('reports.inventory.movement-analysis') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('pagination.clear_filters') }}
                                        </a>
                                        @endif
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

{{-- Movement Logs Modal --}}
<div class="modal fade" id="movementLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="movementLogsTitle">{{ __('pagination.movement_logs') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                <th class="ps-4">{{ __('pagination.date_time') }}</th>
                                <th>{{ __('pagination.reason') }}</th>
                                <th>{{ __('pagination.before') }}</th>
                                <th>{{ __('pagination.after') }}</th>
                                <th>{{ __('pagination.change') }}</th>
                                <th>{{ __('pagination.unit_price') }}</th>
                                <th>{{ __('pagination.customer') }}</th>
                                <th>{{ __('pagination.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody id="movementLogsContent">
                            {{-- Logs will be loaded here via AJAX --}}
                        </tbody>
                    </table>
                </div>
                <div class="text-center py-4" id="movementLogsLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('pagination.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function viewMovementLogs(variantId, productName) {
    // Show modal with loading
    $('#movementLogsTitle').text('Movement Logs: ' + productName);
    $('#movementLogsContent').html('');
    $('#movementLogsLoading').show();
    $('#movementLogsModal').modal('show');
    
    // Get filter values
    const startDate = $('[name="start_date"]').val();
    const endDate = $('[name="end_date"]').val();
    
    // Fetch movement logs
    $.ajax({
        url: '{{ route("reports.inventory.movement-logs") }}',
        method: 'GET',
        data: {
            variant_id: variantId,
            start_date: startDate,
            end_date: endDate,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            $('#movementLogsLoading').hide();
            
            if (response.logs && response.logs.length > 0) {
                let html = '';
                response.logs.forEach(function(log) {
                    const metadata = log.metadata || {};
                    const change = log.quantity_change;
                    const changeColor = change > 0 ? 'success' : (change < 0 ? 'danger' : 'info');
                    const changeSign = change > 0 ? '+' : '';
                    
                    html += `
                    <tr>
                        <td class="ps-4">${new Date(log.created_at).toLocaleString()}</td>
                        <td>
                            <span class="badge ${getReasonBadgeClass(log.reason)}">
                                ${formatReason(log.reason)}
                            </span>
                        </td>
                        <td>${log.quantity_before}</td>
                        <td>${log.quantity_after}</td>
                        <td>
                            <span class="fw-bold text-${changeColor}">
                                ${changeSign}${Math.abs(change)}
                            </span>
                        </td>
                        <td>${metadata.unit_price ? '₦' + parseFloat(metadata.unit_price).toFixed(2) : '-'}</td>
                        <td>${metadata.customer_name || '-'}</td>
                        <td>${log.notes || '-'}</td>
                    </tr>
                    `;
                });
                $('#movementLogsContent').html(html);
            } else {
                $('#movementLogsContent').html(`
                    <tr>
                        <td colspan="8" class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h4 class="text-gray-600 fw-semibold mb-2">No movement logs found</h4>
                        </td>
                    </tr>
                `);
            }
        },
        error: function() {
            $('#movementLogsLoading').hide();
            $('#movementLogsContent').html(`
                <tr>
                    <td colspan="8" class="text-center py-10">
                        <div class="alert alert-danger">
                            Failed to load movement logs. Please try again.
                        </div>
                    </td>
                </tr>
            `);
        }
    });
}

function getReasonBadgeClass(reason) {
    switch(reason) {
        case 'pos_sale':
            return 'badge-light-success';
        case 'return':
            return 'badge-light-warning';
        case 'stock_adjustment':
            return 'badge-light-info';
        case 'purchase':
            return 'badge-light-primary';
        default:
            return 'badge-light-dark';
    }
}

function formatReason(reason) {
    switch(reason) {
        case 'pos_sale':
            return 'POS Sale';
        case 'return':
            return 'Return';
        case 'stock_adjustment':
            return 'Adjustment';
        case 'purchase':
            return 'Purchase';
        default:
            return reason.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
}
</script>
@endpush

@endsection