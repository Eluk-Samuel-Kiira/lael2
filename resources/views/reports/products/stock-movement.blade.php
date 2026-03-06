@extends('layouts.app')

@section('title', __('auth.stock_movement'))

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
                                {{ __('auth.stock_movement') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('auth._dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.stock_movement') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($variants->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'movementTable', filename: 'stock_movement_{{ date('Y_m_d') }}', sheetName: 'Stock Movement'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'movementTable', filename: 'stock_movement_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.products.stock-movement') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-6">
                                        {{-- Category --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group w-100">
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
                                        
                                        {{-- Time Period --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.time_period') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-clock fs-2"></i>
                                                </span>
                                                <select class="form-select" name="days">
                                                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 Days</option>
                                                    <option value="14" {{ $days == 14 ? 'selected' : '' }}>Last 14 Days</option>
                                                    <option value="30" {{ $days == 30 || !request()->has('days') ? 'selected' : '' }}>Last 30 Days</option>
                                                    <option value="60" {{ $days == 60 ? 'selected' : '' }}>Last 60 Days</option>
                                                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 Days</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.products.stock-movement') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.clear') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Movement Summary --}}
                @if($variants->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.movement_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'recent_count', 'color' => 'success', 'icon' => 'ki-check', 'label' => 'recently_updated', 'value' => $movementSummary['recent_count']],
                                        ['key' => 'active_count', 'color' => 'primary', 'icon' => 'ki-clock', 'label' => 'active', 'value' => $movementSummary['active_count']],
                                        ['key' => 'stale_count', 'color' => 'warning', 'icon' => 'ki-hourglass', 'label' => 'stale_items', 'value' => $movementSummary['stale_count']],
                                        ['key' => 'new_this_month', 'color' => 'info', 'icon' => 'ki-add-item', 'label' => 'new_this_month', 'value' => $movementSummary['new_this_month']],
                                        ['key' => 'updated_this_week', 'color' => 'danger', 'icon' => 'ki-refresh', 'label' => 'updated_this_week', 'value' => $movementSummary['updated_this_week']],
                                        ['key' => 'total_items', 'color' => 'secondary', 'icon' => 'ki-abstract-44', 'label' => 'total_items', 'value' => $variants->count()]
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
                                                    {{ __('auth.' . $stat['label']) }}
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

                {{-- Movement Charts --}}
                <div class="row mb-6">
                    {{-- Movement Status Distribution --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.movement_status_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="movementStatusChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Activity Timeline --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.activity_timeline') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="activityTimelineChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.recent_activity') }}</h3>
                                    </div>
                                    @if($variants->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $variants->count() }} {{ __('auth.items') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="movementTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('auth.sku') }}</th>
                                                <th>{{ __('accounting.name') }}</th>
                                                <th>{{ __('accounting.category') }}</th>
                                                <th>{{ __('auth.quantity') }}</th>
                                                <th>{{ __('auth.created_date') }}</th>
                                                <th>{{ __('auth.last_updated') }}</th>
                                                <th>{{ __('auth.days_since_update') }}</th>
                                                <th>{{ __('auth.days_since_creation') }}</th>
                                                <th>{{ __('auth.movement_status') }}</th>
                                                <th>{{ __('auth.activity') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($variants as $variant)
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $variant->sku }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($variant->image_url)
                                                        <div class="symbol symbol-50px me-3">
                                                            <img src="{{ asset($variant->image_url) }}" alt="{{ $variant->name }}" class="rounded">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <span class="fw-bold text-gray-800">{{ $variant->name }}</span>
                                                            @if($variant->product)
                                                            <div class="text-muted fs-7">{{ $variant->product->name }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($variant->product && $variant->product->category)
                                                    <span class="badge badge-light-info">{{ $variant->product->category->name }}</span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $variant->overal_quantity_at_hand }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $variant->created_at->format('Y-m-d') }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-semibold">{{ $variant->updated_at->format('Y-m-d H:i') }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $variant->days_since_update <= 7 ? 'success' : ($variant->days_since_update <= 30 ? 'warning' : 'danger') }}">
                                                        {{ $variant->days_since_update }} {{ __('auth.days') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $variant->days_since_creation }} {{ __('auth.days') }}</span>
                                                </td>
                                                <td>
                                                    @if($variant->movement_status == 'recent')
                                                        <span class="badge badge-success">{{ $variant->movement_label }}</span>
                                                    @elseif($variant->movement_status == 'active')
                                                        <span class="badge badge-primary">{{ $variant->movement_label }}</span>
                                                    @else
                                                        <span class="badge badge-warning">{{ $variant->movement_label }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($variant->days_since_update <= 7)
                                                        <i class="ki-duotone ki-check-circle fs-2 text-success"></i>
                                                    @elseif($variant->days_since_update <= 30)
                                                        <i class="ki-duotone ki-clock fs-2 text-primary"></i>
                                                    @else
                                                        <i class="ki-duotone ki-hourglass fs-2 text-warning"></i>
                                                    @endif
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.no_products_found') }}</p>
                                        @if(request()->hasAny(['category_id', 'days']))
                                        <a href="{{ route('reports.products.stock-movement') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('accounting.clear_filters_view_all') }}
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

@push('scripts')
@if($variants->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for charts
        @php
            // Calculate movement status distribution
            $recentCount = $variants->where('movement_status', 'recent')->count();
            $activeCount = $variants->where('movement_status', 'active')->count();
            $staleCount = $variants->where('movement_status', 'stale')->count();
            
            // Calculate activity by days (last 30 days)
            $activityByDay = [];
            for ($i = 0; $i < 30; $i++) {
                $date = \Carbon\Carbon::now()->subDays($i);
                $count = $variants->where('updated_at', '>=', $date->copy()->startOfDay())
                    ->where('updated_at', '<=', $date->copy()->endOfDay())
                    ->count();
                $activityByDay[] = [
                    'date' => $date->format('M d'),
                    'count' => $count
                ];
            }
            $activityByDay = array_reverse($activityByDay);
        @endphp
        
        // Movement Status Distribution Chart (Pie Chart)
        const movementStatusChart = new ApexCharts(document.querySelector("#movementStatusChart"), {
            series: [{{ $recentCount }}, {{ $activeCount }}, {{ $staleCount }}],
            chart: {
                type: 'pie',
                height: 300,
                toolbar: {
                    show: true,
                    tools: {
                        download: true
                    }
                }
            },
            labels: [
                '{{ __("auth.recently_updated") }} (≤7 days)',
                '{{ __("auth.active") }} (8-30 days)',
                '{{ __("auth.stale") }} (>30 days)'
            ],
            colors: ['#50CD89', '#3E97FF', '#FFC700'],
            legend: {
                position: 'bottom',
                fontSize: '12px'
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' {{ __("auth.item") }}' + (value !== 1 ? 's' : '');
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    return opts.w.config.series[opts.seriesIndex];
                }
            }
        });
        movementStatusChart.render();
        
        // Activity Timeline Chart (Line Chart)
        const activityTimelineChart = new ApexCharts(document.querySelector("#activityTimelineChart"), {
            series: [{
                name: '{{ __("auth.updates") }}',
                data: @json(array_column($activityByDay, 'count'))
            }],
            chart: {
                type: 'line',
                height: 300,
                toolbar: {
                    show: true,
                    tools: {
                        download: true
                    }
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            xaxis: {
                categories: @json(array_column($activityByDay, 'date')),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '10px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("auth.number_of_updates") }}'
                },
                min: 0
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' {{ __("auth.update") }}' + (value !== 1 ? 's' : '');
                    }
                }
            },
            markers: {
                size: 5
            }
        });
        activityTimelineChart.render();
        
        // Add export functionality
        window.exportCurrentPage = function(options) {
            const { tableId, filename, sheetName, format = 'excel' } = options;
            const table = document.getElementById(tableId);
            
            if (!table) {
                alert('Table not found');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Clean text - remove HTML tags and trim
                    let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").trim();
                    // Escape quotes and wrap in quotes if contains comma
                    if (text.includes(',') || text.includes('"')) {
                        text = '"' + text.replace(/"/g, '""') + '"';
                    }
                    row.push(text);
                }
                csv.push(row.join(","));
            }
            
            const csvContent = csv.join("\n");
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            
            if (navigator.msSaveBlob) {
                navigator.msSaveBlob(blob, filename + '.csv');
            } else {
                link.href = URL.createObjectURL(blob);
                link.setAttribute("download", filename + '.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        };
    });
</script>
@endif
@endpush

@endsection