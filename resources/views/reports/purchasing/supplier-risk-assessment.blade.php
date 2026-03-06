{{-- resources/views/reports/purchasing/supplier-risk-assessment.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.supplier_risk_assessment'))

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
                                {{ __('pagination.supplier_risk_assessment') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.supplier_risk_assessment') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($suppliers->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="exportReport('excel')">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <!-- <li>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="exportReport('pdf')">
                                            <i class="ki-duotone ki-file-pdf fs-2 me-2 text-danger"></i>
                                            {{ __('pagination.export_to_pdf') }}
                                        </a>
                                    </li> -->
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Risk Overview --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-shield fs-4tx text-primary me-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div>
                                        <h3 class="fw-bold text-gray-800 mb-2">{{ __('passwords.supplier_risk_assessment') }}</h3>
                                        <p class="text-muted mb-0">
                                            {{ __('passwords.risk_assessment_description') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Risk Summary Cards --}}
                <div class="row mb-6">
                    @php
                        $totalSuppliers = $suppliers->count();
                        $highRiskPercentage = $totalSuppliers > 0 ? round(($riskDistribution['high'] / $totalSuppliers) * 100, 1) : 0;
                        $mediumRiskPercentage = $totalSuppliers > 0 ? round(($riskDistribution['medium'] / $totalSuppliers) * 100, 1) : 0;
                        $lowRiskPercentage = $totalSuppliers > 0 ? round(($riskDistribution['low'] / $totalSuppliers) * 100, 1) : 0;
                        
                        $summaryCards = [
                            [
                                'title' => __('passwords.total_suppliers_assessed'),
                                'value' => number_format($totalSuppliers),
                                'color' => 'primary',
                                'icon' => 'ki-profile-user',
                                'description' => __('passwords.active_suppliers_analyzed')
                            ],
                            [
                                'title' => __('passwords.high_risk_suppliers'),
                                'value' => $riskDistribution['high'],
                                'color' => 'danger',
                                'icon' => 'ki-exclamation',
                                'description' => $highRiskPercentage . '% ' . __('passwords.of_total')
                            ],
                            [
                                'title' => __('passwords.medium_risk_suppliers'),
                                'value' => $riskDistribution['medium'],
                                'color' => 'warning',
                                'icon' => 'ki-information',
                                'description' => $mediumRiskPercentage . '% ' . __('passwords.of_total')
                            ],
                            [
                                'title' => __('passwords.low_risk_suppliers'),
                                'value' => $riskDistribution['low'],
                                'color' => 'success',
                                'icon' => 'ki-check',
                                'description' => $lowRiskPercentage . '% ' . __('passwords.of_total')
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

                {{-- Risk Analysis --}}
                <div class="row mb-6">
                    {{-- Risk Distribution Chart --}}
                    <div class="col-lg-8 mb-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.risk_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="riskDistributionChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Risk Legend --}}
                    <div class="col-lg-4 mb-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-information fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.risk_legend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bullet bullet-dot bg-danger h-10px w-10px me-3"></div>
                                        <div class="fw-bold text-gray-800">{{ __('passwords.high_risk') }}</div>
                                    </div>
                                    <p class="text-muted fs-7 mb-0">
                                        {{ __('passwords.high_risk_description') }}
                                    </p>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bullet bullet-dot bg-warning h-10px w-10px me-3"></div>
                                        <div class="fw-bold text-gray-800">{{ __('passwords.medium_risk') }}</div>
                                    </div>
                                    <p class="text-muted fs-7 mb-0">
                                        {{ __('passwords.medium_risk_description') }}
                                    </p>
                                </div>
                                
                                <div>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bullet bullet-dot bg-success h-10px w-10px me-3"></div>
                                        <div class="fw-bold text-gray-800">{{ __('passwords.low_risk') }}</div>
                                    </div>
                                    <p class="text-muted fs-7 mb-0">
                                        {{ __('passwords.low_risk_description') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Critical Suppliers --}}
                @if($criticalSuppliers->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-danger fs-2 me-2 text-danger">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.critical_suppliers') }}</h3>
                                    </div>
                                    <span class="badge badge-light-danger fs-7">
                                        {{ $criticalSuppliers->count() }} {{ __('passwords.suppliers_need_attention') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.risk_score') }}</th>
                                                <th>{{ __('passwords.total_spent') }}</th>
                                                <th>{{ __('passwords.payment_terms') }}</th>
                                                <th>{{ __('passwords.delivery_performance') }}</th>
                                                <th>{{ __('passwords.primary_risk_factors') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($criticalSuppliers as $supplier)
                                            @php
                                                $totalSpent = $supplier->total_spent ?? 0;
                                                
                                                // Get risk factors
                                                $riskFactors = $supplier->risk_factors ?? [];
                                                $primaryFactors = array_slice($riskFactors, 0, 3);
                                            @endphp
                                            <tr class="table-danger">
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-50px me-3">
                                                            <div class="symbol-label bg-light-danger text-danger fw-bold">
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
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100px me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-danger" 
                                                                style="width: {{ min(100, $supplier->risk_score ?? 0) }}%"></div>
                                                        </div>
                                                        <span class="fw-bold text-danger">{{ number_format($supplier->risk_score ?? 0, 1) }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($totalSpent, 2) }}
                                                    </span>
                                                    <div class="text-muted fs-8">
                                                        {{ $supplier->total_orders ?? 0 }} {{ __('passwords.orders') }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ ($supplier->payment_terms_days ?? 0) > 60 ? 'danger' : (($supplier->payment_terms_days ?? 0) > 30 ? 'warning' : 'success') }}">
                                                        {{ $supplier->payment_terms_days ?? 30 }} {{ __('passwords.days') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="fw-semibold {{ ($supplier->avg_delivery_days ?? 0) > 14 ? 'text-danger' : (($supplier->avg_delivery_days ?? 0) > 7 ? 'text-warning' : 'text-success') }}">
                                                            {{ number_format($supplier->avg_delivery_days ?? 0, 1) }} {{ __('passwords.days') }}
                                                        </span>
                                                    </div>
                                                    <div class="text-muted fs-8">
                                                        {{ $supplier->delivered_orders ?? 0 }} {{ __('passwords.delivered_orders') }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($primaryFactors as $factor)
                                                        <span class="badge badge-light-danger fs-8">{{ __("passwords.{$factor}") }}</span>
                                                        @endforeach
                                                        @if(count($riskFactors) > 3)
                                                        <span class="badge badge-light-dark fs-8">+{{ count($riskFactors) - 3 }}</span>
                                                        @endif
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
                @endif

                {{-- All Suppliers --}}
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.all_suppliers_assessment') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ $suppliers->count() }} {{ __('passwords.suppliers') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.risk_level') }}</th>
                                                <th>{{ __('passwords.risk_score') }}</th>
                                                <th>{{ __('passwords.financial_impact') }}</th>
                                                <th>{{ __('passwords.delivery_performance') }}</th>
                                                <th>{{ __('passwords.contract_terms') }}</th>
                                                <th>{{ __('passwords.risk_factors') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($suppliers as $index => $supplier)
                                            @php
                                                // Risk level configuration
                                                $riskLevelColors = [
                                                    'high' => 'danger',
                                                    'medium' => 'warning',
                                                    'low' => 'success'
                                                ];
                                                $riskLevelIcons = [
                                                    'high' => 'ki-exclamation',
                                                    'medium' => 'ki-information',
                                                    'low' => 'ki-check'
                                                ];
                                                $riskColor = $riskLevelColors[$supplier->risk_level] ?? 'dark';
                                                $riskIcon = $riskLevelIcons[$supplier->risk_level] ?? 'ki-user';
                                                
                                                // Financial impact
                                                $totalSpent = $supplier->total_spent ?? 0;
                                                $financialImpact = 'low';
                                                $financialColor = 'success';
                                                if ($totalSpent > 50000) {
                                                    $financialImpact = 'critical';
                                                    $financialColor = 'danger';
                                                } elseif ($totalSpent > 10000) {
                                                    $financialImpact = 'high';
                                                    $financialColor = 'warning';
                                                } elseif ($totalSpent > 1000) {
                                                    $financialImpact = 'medium';
                                                    $financialColor = 'info';
                                                }
                                                
                                                // Delivery performance
                                                $deliveryPerformance = 'good';
                                                $deliveryColor = 'success';
                                                $avgDeliveryDays = $supplier->avg_delivery_days ?? 0;
                                                if ($avgDeliveryDays > 21) {
                                                    $deliveryPerformance = 'poor';
                                                    $deliveryColor = 'danger';
                                                } elseif ($avgDeliveryDays > 14) {
                                                    $deliveryPerformance = 'average';
                                                    $deliveryColor = 'warning';
                                                } elseif ($avgDeliveryDays > 7) {
                                                    $deliveryPerformance = 'moderate';
                                                    $deliveryColor = 'info';
                                                }
                                                
                                                // Contract terms
                                                $contractTerms = 'favorable';
                                                $contractColor = 'success';
                                                $paymentTerms = $supplier->payment_terms_days ?? 30;
                                                if ($paymentTerms > 60) {
                                                    $contractTerms = 'unfavorable';
                                                    $contractColor = 'danger';
                                                } elseif ($paymentTerms > 45) {
                                                    $contractTerms = 'extended';
                                                    $contractColor = 'warning';
                                                } elseif ($paymentTerms > 30) {
                                                    $contractTerms = 'standard';
                                                    $contractColor = 'info';
                                                }
                                                
                                                // Risk factors
                                                $riskFactors = $supplier->risk_factors ?? [];
                                                $displayFactors = array_slice($riskFactors, 0, 2);
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $loop->iteration }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-50px me-3">
                                                            <div class="symbol-label bg-light-{{ $riskColor }} text-{{ $riskColor }} fw-bold">
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
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-duotone {{ $riskIcon }} fs-2 text-{{ $riskColor }} me-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        <span class="badge badge-light-{{ $riskColor }}">
                                                            {{ __("passwords.{$supplier->risk_level}_risk") }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100px me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-{{ $riskColor }}" 
                                                                style="width: {{ min(100, $supplier->risk_score ?? 0) }}%"></div>
                                                        </div>
                                                        <span class="fw-bold text-{{ $riskColor }}">{{ number_format($supplier->risk_score ?? 0, 1) }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="badge badge-light-{{ $financialColor }} mb-1">
                                                            {{ __("passwords.{$financialImpact}_impact") }}
                                                        </span>
                                                        <span class="text-muted fs-8">
                                                            ${{ number_format($totalSpent, 0) }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="badge badge-light-{{ $deliveryColor }} mb-1">
                                                            {{ __("passwords.{$deliveryPerformance}_delivery") }}
                                                        </span>
                                                        <span class="text-muted fs-8">
                                                            {{ $avgDeliveryDays }} {{ __('passwords.days_avg') }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="badge badge-light-{{ $contractColor }} mb-1">
                                                            {{ __("passwords.{$contractTerms}_terms") }}
                                                        </span>
                                                        <span class="text-muted fs-8">
                                                            {{ $paymentTerms }} {{ __('passwords.days') }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($displayFactors as $factor)
                                                        <span class="badge badge-light-{{ $riskColor }} fs-8">{{ __("passwords.{$factor}") }}</span>
                                                        @endforeach
                                                        @if(count($riskFactors) > 2)
                                                        <span class="badge badge-light-dark fs-8">+{{ count($riskFactors) - 2 }}</span>
                                                        @endif
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
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center py-10">
                                    <i class="ki-duotone ki-shield fs-4tx text-gray-400 mb-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_supplier_risk_data') }}</h4>
                                    <p class="text-muted fs-6">{{ __('passwords.no_suppliers_available_for_risk_assessment') }}</p>
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
@if($suppliers->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Risk Distribution Chart
        @php
            $riskData = [
                $riskDistribution['high'] ?? 0,
                $riskDistribution['medium'] ?? 0,
                $riskDistribution['low'] ?? 0
            ];
            
            $riskLabels = [
                __('passwords.high_risk'),
                __('passwords.medium_risk'),
                __('passwords.low_risk')
            ];
            
            $riskColors = ['#F1416C', '#FFC700', '#50CD89'];
        @endphp

        const riskDistributionChart = new ApexCharts(document.querySelector("#riskDistributionChart"), {
            series: @json($riskData),
            chart: {
                type: 'donut',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            labels: @json($riskLabels),
            colors: @json($riskColors),
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
                                formatter: function() {
                                    return {{ $suppliers->count() }}
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' suppliers'
                    }
                }
            }
        });
        
        riskDistributionChart.render();
        
        // Simple export function
        function exportReport(format) {
            // Your existing export implementation
            console.log('Exporting as', format);
        }
        
        window.exportReport = exportReport;
    });
</script>
@endif
@endpush
@endsection