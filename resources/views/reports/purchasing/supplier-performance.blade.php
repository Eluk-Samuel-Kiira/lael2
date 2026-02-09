{{-- resources/views/reports/purchasing/supplier-performance.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.supplier_performance'))

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
                                {{ __('pagination.supplier_performance') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.supplier_performance') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($suppliers->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('pagination.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'suppliersTable', filename: 'supplier_performance_{{ date('Y_m_d') }}', sheetName: 'Supplier Performance'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'suppliersTable', filename: 'supplier_performance_{{ date('Y_m_d') }}', format: 'csv'})">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('pagination.export_to_csv') }}
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.purchasing.supplier-performance') }}" id="filterForm">
                                    <div class="row g-4 mb-4">
                                        {{-- Date Range --}}
                                        <div class="col-md-4">
                                            <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                            <input type="date" class="form-control" name="start_date" 
                                                value="{{ $startDate }}" required>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                            <input type="date" class="form-control" name="end_date" 
                                                value="{{ $endDate }}" required>
                                        </div>
                                        
                                        {{-- Supplier --}}
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                            <select class="form-select" name="supplier_id">
                                                <option value="">{{ __('passwords.all_suppliers') }}</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" 
                                                            {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('pagination.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.purchasing.supplier-performance') }}" class="btn btn-light btn-active-light-primary">
                                                    <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                                    {{ __('pagination.clear_filters') }}
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
                <div class="row mb-6">
                    @php
                        $summaryCards = [
                            [
                                'title' => __('passwords.total_suppliers'),
                                'value' => $summary['total_suppliers'],
                                'color' => 'primary',
                                'icon' => 'ki-profile-user',
                                'description' => __('passwords.active_suppliers')
                            ],
                            [
                                'title' => __('passwords.total_spent'),
                                'value' => '$' . number_format($summary['total_spent'], 2),
                                'color' => 'success',
                                'icon' => 'ki-dollar',
                                'description' => __('passwords.total_purchases')
                            ],
                            [
                                'title' => __('passwords.avg_order_value'),
                                'value' => '$' . number_format($summary['avg_order_value'], 2),
                                'color' => 'info',
                                'icon' => 'ki-chart-line',
                                'description' => __('passwords.per_order_average')
                            ],
                            [
                                'title' => __('passwords.top_supplier'),
                                'value' => $summary['top_supplier'] ? $summary['top_supplier']->name : __('passwords.none'),
                                'color' => 'warning',
                                'icon' => 'ki-crown',
                                'description' => $summary['top_supplier'] ? '$' . number_format($summary['top_supplier']->total_spent, 2) : ''
                            ]
                        ];
                    @endphp
                    
                    @foreach($summaryCards as $card)
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card card-flush bg-light-{{ $card['color'] }} border border-{{ $card['color'] }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone {{ $card['icon'] }} fs-2tx text-{{ $card['color'] }} me-3">
                                        @for($i = 1; $i <= 2; $i++)
                                        <span class="path{{ $i }}"></span>
                                        @endfor
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ $card['title'] }}</div>
                                        <div class="fs-2 fw-bold text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                                        @if($card['description'])
                                        <div class="text-muted fs-7">{{ $card['description'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Charts Section --}}
                @if($suppliers->count() > 0)
                @php
                    // Collect chart data manually since $suppliers is a paginator
                    $abcCounts = ['A' => 0, 'B' => 0, 'C' => 0];
                    $supplierData = [];
                    $deliveryRanges = ['excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0];
                    $orderValueRanges = ['high' => 0, 'medium' => 0, 'low' => 0, 'very_low' => 0];
                    
                    foreach ($suppliers as $supplier) {
                        // Count ABC classifications
                        $classification = $supplier->classification ?? 'C';
                        $abcCounts[$classification]++;
                        
                        // Store supplier data for top 10 chart
                        $supplierData[] = [
                            'name' => $supplier->name,
                            'total_spent' => $supplier->total_spent ?? 0
                        ];
                        
                        // Count delivery performance
                        $deliveryRate = $supplier->on_time_delivery_rate ?? 0;
                        if ($deliveryRate >= 90) {
                            $deliveryRanges['excellent']++;
                        } elseif ($deliveryRate >= 75) {
                            $deliveryRanges['good']++;
                        } elseif ($deliveryRate >= 60) {
                            $deliveryRanges['fair']++;
                        } else {
                            $deliveryRanges['poor']++;
                        }
                        
                        // Count order value distribution
                        $avgValue = $supplier->avg_order_value ?? 0;
                        if ($avgValue >= 1000) {
                            $orderValueRanges['high']++;
                        } elseif ($avgValue >= 500) {
                            $orderValueRanges['medium']++;
                        } elseif ($avgValue >= 100) {
                            $orderValueRanges['low']++;
                        } else {
                            $orderValueRanges['very_low']++;
                        }
                    }
                    
                    // Get top 10 suppliers by spend
                    usort($supplierData, function($a, $b) {
                        return $b['total_spent'] <=> $a['total_spent'];
                    });
                    
                    $topSuppliers = array_slice($supplierData, 0, min(10, count($supplierData)));
                    $topSupplierNames = array_map(function($supplier) {
                        $name = $supplier['name'];
                        return strlen($name) > 20 ? substr($name, 0, 20) . '...' : $name;
                    }, $topSuppliers);
                    $topSupplierSpends = array_column($topSuppliers, 'total_spent');
                @endphp

                <div class="row mb-6">
                    {{-- ABC Classification Distribution --}}
                    <div class="col-lg-6 mb-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.abc_classification_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="abcClassificationChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Top 10 Suppliers by Spend --}}
                    <div class="col-lg-6 mb-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.top_10_suppliers_by_spend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="topSuppliersChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Performance Metrics Charts --}}
                <div class="row mb-6">
                    {{-- Delivery Performance --}}
                    <div class="col-lg-6 mb-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-delivery fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.delivery_performance') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="deliveryPerformanceChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Order Value Distribution --}}
                    <div class="col-lg-6 mb-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.order_value_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="orderValueDistributionChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Suppliers Performance Table --}}
                @if($suppliers->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.supplier_performance_metrics') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $suppliers->count() }} {{ __('pagination.suppliers') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="suppliersTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.abc_classification') }}</th>
                                                <th>{{ __('passwords.total_orders') }}</th>
                                                <th>{{ __('passwords.total_spent') }}</th>
                                                <th>{{ __('passwords.spend_percentage') }}</th>
                                                <th>{{ __('passwords.avg_order_value') }}</th>
                                                <th>{{ __('passwords.on_time_delivery_rate') }}</th>
                                                <th>{{ __('passwords.avg_delivery_days') }}</th>
                                                <th>{{ __('passwords.performance_score') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($suppliers as $index => $supplier)
                                            @php
                                                // Determine classification colors
                                                $classificationColors = [
                                                    'A' => 'danger',
                                                    'B' => 'warning',
                                                    'C' => 'success'
                                                ];
                                                $classColor = $classificationColors[$supplier->classification] ?? 'dark';
                                                
                                                // Calculate performance score
                                                $performanceScore = (
                                                    ($supplier->total_spent > 0 ? 30 : 0) +
                                                    ($supplier->on_time_delivery_rate * 0.4) +
                                                    (max(0, 100 - ($supplier->avg_delivery_days * 2)) * 0.3)
                                                );
                                                
                                                // Determine performance color
                                                if ($performanceScore >= 80) {
                                                    $performanceColor = 'success';
                                                    $performanceLabel = __('passwords.excellent');
                                                } elseif ($performanceScore >= 60) {
                                                    $performanceColor = 'info';
                                                    $performanceLabel = __('passwords.good');
                                                } elseif ($performanceScore >= 40) {
                                                    $performanceColor = 'warning';
                                                    $performanceLabel = __('passwords.fair');
                                                } else {
                                                    $performanceColor = 'danger';
                                                    $performanceLabel = __('passwords.poor');
                                                }
                                                
                                                // Delivery performance color
                                                $deliveryColor = $supplier->on_time_delivery_rate >= 90 ? 'success' : 
                                                                ($supplier->on_time_delivery_rate >= 75 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $loop->iteration }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-50px me-3">
                                                            <div class="symbol-label bg-light-{{ $classColor }} text-{{ $classColor }} fw-bold">
                                                                {{ strtoupper(substr($supplier->name, 0, 2)) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $supplier->name }}</div>
                                                            <small class="text-muted">{{ $supplier->contact_person ?? __('pagination.no_contact') }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $classColor }}">
                                                        @php
                                                            // Try to get classification - handle both array and object
                                                            if (is_array($supplier) && isset($supplier['classification'])) {
                                                                $classification = $supplier['classification'];
                                                            } elseif (is_object($supplier) && isset($supplier->classification)) {
                                                                $classification = $supplier->classification;
                                                            } else {
                                                                $classification = 'C';
                                                            }
                                                            
                                                            // Simple mapping since translations might not be working
                                                            $categoryLabels = [
                                                                'A' => 'Category A (Strategic)',
                                                                'B' => 'Category B (Tactical)',
                                                                'C' => 'Category C (Transactional)'
                                                            ];
                                                            
                                                            echo $categoryLabels[$classification] ?? $classification . ' Category';
                                                        @endphp
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ $supplier->total_orders }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($supplier->total_spent, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="fw-bold me-2">{{ number_format($supplier->spend_percentage, 1) }}%</span>
                                                        <div class="progress w-100px" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $classColor }}" 
                                                                style="width: {{ min(100, $supplier->spend_percentage) }}%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold">${{ number_format($supplier->avg_order_value, 2) }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge badge-light-{{ $deliveryColor }} me-2">
                                                            {{ number_format($supplier->on_time_delivery_rate, 1) }}%
                                                        </span>
                                                        @if($supplier->on_time_delivery_rate >= 90)
                                                        <i class="ki-duotone ki-like fs-2 text-success">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        @elseif($supplier->on_time_delivery_rate >= 75)
                                                        <i class="ki-duotone ki-clock fs-2 text-warning">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        @else
                                                        <i class="ki-duotone ki-cross-circle fs-2 text-danger">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold {{ $supplier->avg_delivery_days <= 7 ? 'text-success' : ($supplier->avg_delivery_days <= 14 ? 'text-warning' : 'text-danger') }}">
                                                        {{ number_format($supplier->avg_delivery_days, 1) }} {{ __('pagination.days') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100px me-3" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $performanceColor }}" 
                                                                style="width: {{ $performanceScore }}%"></div>
                                                        </div>
                                                        <span class="badge badge-light-{{ $performanceColor }}">
                                                            {{ $performanceLabel }}
                                                        </span>
                                                    </div>
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
                                        <i class="ki-duotone ki-profile-user fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_suppliers') }}</h4>
                                        <p class="text-muted fs-6">{{ __('passwords.no_supplier_performance_data') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'supplier_id']))
                                        <a href="{{ route('reports.purchasing.supplier-performance') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('passwords.clear_filters_view_all') }}
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

@push('styles')
<style>
    .apexcharts-tooltip {
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    .performance-score {
        min-width: 100px;
    }
</style>
@endpush

@push('scripts')
@if($suppliers->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.querySelector('[name="start_date"]').value);
            const endDate = new Date(document.querySelector('[name="end_date"]').value);
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('{{ __("pagination.start_date_cannot_be_after_end_date") }}');
                return false;
            }
        });

        // Chart 1: ABC Classification Distribution
        const abcClassificationChart = new ApexCharts(document.querySelector("#abcClassificationChart"), {
            series: [{{ $abcCounts['A'] }}, {{ $abcCounts['B'] }}, {{ $abcCounts['C'] }}],
            chart: {
                type: 'donut',
                height: 300
            },
            labels: ['Category A (Strategic)', 'Category B (Tactical)', 'Category C (Transactional)'],
            colors: ['#F1416C', '#FFC700', '#50CD89'],
            legend: {
                position: 'bottom'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Suppliers',
                                formatter: function(w) {
                                    return {{ $suppliers->count() }}
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' suppliers'
                    }
                }
            }
        });
        abcClassificationChart.render();
        
        // Chart 2: Top 10 Suppliers by Spend
        const topSuppliersChart = new ApexCharts(document.querySelector("#topSuppliersChart"), {
            series: [{
                name: 'Total Spent',
                data: @json($topSupplierSpends)
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return '$' + val.toLocaleString('en-US', {minimumFractionDigits: 0})
                },
                offsetX: 20,
                style: {
                    fontSize: '12px',
                    colors: ['#304758']
                }
            },
            xaxis: {
                categories: @json($topSupplierNames),
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString('en-US', {minimumFractionDigits: 0})
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
                        return '$' + val.toLocaleString('en-US', {minimumFractionDigits: 2})
                    }
                }
            }
        });
        topSuppliersChart.render();
        
        // Chart 3: Delivery Performance
        const deliveryPerformanceChart = new ApexCharts(document.querySelector("#deliveryPerformanceChart"), {
            series: [{
                name: 'Suppliers',
                data: [
                    {{ $deliveryRanges['excellent'] }},
                    {{ $deliveryRanges['good'] }},
                    {{ $deliveryRanges['fair'] }},
                    {{ $deliveryRanges['poor'] }}
                ]
            }],
            chart: {
                type: 'radialBar',
                height: 300
            },
            plotOptions: {
                radialBar: {
                    dataLabels: {
                        name: {
                            fontSize: '22px',
                        },
                        value: {
                            fontSize: '16px',
                        },
                        total: {
                            show: true,
                            label: 'Delivery Performance',
                            formatter: function(w) {
                                const total = {{ $deliveryRanges['excellent'] + $deliveryRanges['good'] + $deliveryRanges['fair'] + $deliveryRanges['poor'] }};
                                const excellent = {{ $deliveryRanges['excellent'] }};
                                return excellent > 0 ? Math.round((excellent / total) * 100) + '%' : '0%'
                            }
                        }
                    }
                }
            },
            labels: ['≥ 90%', '75-89%', '60-74%', '< 60%'],
            colors: ['#50CD89', '#FFC700', '#FF9F1C', '#F1416C'],
            legend: {
                show: true,
                position: 'bottom'
            }
        });
        deliveryPerformanceChart.render();
        
        // Chart 4: Order Value Distribution
        const orderValueDistributionChart = new ApexCharts(document.querySelector("#orderValueDistributionChart"), {
            series: [{
                name: 'Suppliers',
                data: [
                    {{ $orderValueRanges['high'] }},
                    {{ $orderValueRanges['medium'] }},
                    {{ $orderValueRanges['low'] }},
                    {{ $orderValueRanges['very_low'] }}
                ]
            }],
            chart: {
                type: 'polarArea',
                height: 300
            },
            labels: ['≥ $1,000', '$500-999', '$100-499', '< $100'],
            colors: ['#F1416C', '#FFC700', '#3E97FF', '#50CD89'],
            stroke: {
                colors: ['#fff']
            },
            fill: {
                opacity: 0.8
            },
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' suppliers'
                    }
                }
            },
            yaxis: {
                show: false
            }
        });
        orderValueDistributionChart.render();
    });
</script>
@endif

<script>
    // Export function for current page
    function exportCurrentPage({tableId, filename, sheetName, format = 'xlsx'}) {
        // Implementation for export functionality
        console.log(`Exporting ${tableId} as ${format}`);
        // Add your export logic here
    }
</script>
@endpush
@endsection