<x-app-layout>
    @section('title', __('payments.overview_dashboard'))
    @section('content')
        
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('payments.financial_overview')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        @php
                            $previousUrl = url()->previous();
                            $previousRouteName = optional(app('router')->getRoutes()->match(request()->create($previousUrl)))->getName();
                            $formattedRouteName = $previousRouteName 
                                ? Str::of($previousRouteName)->replace('.', ' ')->title() 
                                : __('payments.back');
                        @endphp
                        <a href="{{ $previousUrl }}" class="text-muted text-hover-primary">
                            {{ $formattedRouteName }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('payments.financial_reports')}}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Date Filter Card -->
                <div class="card mb-6">
                    <div class="card-body">
                        <form method="GET" action="{{ route('overview') }}" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{__('payments.period')}}</label>
                                <select name="filter_type" class="form-select" onchange="this.form.submit()">
                                    <option value="today" {{ $filterType == 'today' ? 'selected' : '' }}>{{__('payments.today')}}</option>
                                    <option value="yesterday" {{ $filterType == 'yesterday' ? 'selected' : '' }}>{{__('payments.yesterday')}}</option>
                                    <option value="this_week" {{ $filterType == 'this_week' ? 'selected' : '' }}>{{__('payments.this_week')}}</option>
                                    <option value="this_month" {{ $filterType == 'this_month' ? 'selected' : '' }}>{{__('payments.this_month')}}</option>
                                    <option value="custom" {{ $filterType == 'custom' ? 'selected' : '' }}>{{__('payments.custom_range')}}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{__('payments.start_date')}}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" {{ $filterType != 'custom' ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{__('payments.end_date')}}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" {{ $filterType != 'custom' ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ki-duotone ki-filter fs-2"></i>{{__('payments.apply_filter')}}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Financial Summary Cards -->
                <div class="row g-5 g-xl-8 mb-6">
                    <div class="col-xl-3">
                        <div class="card card-xl-stretch mb-5 mb-xl-8">
                            <div class="card-body">
                                <span class="text-gray-600 fw-bold fs-6">{{__('payments.total_sales')}}</span>
                                <div class="d-flex align-items-center mt-3">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-duotone ki-chart-line-up fs-2x text-primary"></i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bolder fs-2x text-gray-900">{{ number_format(($financialSummary->total_sales ?? 0) / 100, 2) }}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">{{ $financialSummary->order_count ?? 0 }} {{__('payments.transactions')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3">
                        <div class="card card-xl-stretch mb-5 mb-xl-8">
                            <div class="card-body">
                                <span class="text-gray-600 fw-bold fs-6">{{__('payments.gross_profit')}}</span>
                                <div class="d-flex align-items-center mt-3">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-light-success">
                                            <i class="ki-duotone ki-chart-line-down fs-2x text-success"></i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bolder fs-2x text-gray-900">{{ number_format(($profitData->gross_profit ?? 0) / 100, 2) }}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">
                                            {{ number_format((($profitData->gross_profit ?? 0) / max($profitData->revenue ?? 1, 1)) * 100, 1) }}% {{__('payments.margin')}}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3">
                        <div class="card card-xl-stretch mb-5 mb-xl-8">
                            <div class="card-body">
                                <span class="text-gray-600 fw-bold fs-6">{{__('payments.average_order')}}</span>
                                <div class="d-flex align-items-center mt-3">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-light-warning">
                                            <i class="ki-duotone ki-chart-simple fs-2x text-warning"></i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bolder fs-2x text-gray-900">{{ number_format(($financialSummary->average_order ?? 0) / 100, 2) }}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">{{__('payments.per_transaction')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3">
                        <div class="card card-xl-stretch mb-5 mb-xl-8">
                            <div class="card-body">
                                <span class="text-gray-600 fw-bold fs-6">{{__('payments.tax_collected')}}</span>
                                <div class="d-flex align-items-center mt-3">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-light-info">
                                            <i class="ki-duotone ki-chart-simple-2 fs-2x text-info"></i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bolder fs-2x text-gray-900">{{ number_format(($expenseSummary['tax_collected']), 2) }}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">{{__('payments.net_of_discounts')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Breakdowns -->
                <div class="row g-5 g-xl-8 mb-6">
                    <!-- Hourly Breakdown -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-800">{{__('payments.hourly_sales_breakdown')}}</span>
                                    <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.peak_hours_analysis')}}</span>
                                </h3>
                            </div>
                            <div class="card-body pt-0">
                                <div id="hourly_sales_chart" class="h-350px"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-800">{{__('payments.payment_methods')}}</span>
                                    <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.transaction_volume_by_method')}}</span>
                                </h3>
                            </div>
                            <div class="card-body pt-0">
                                <div id="payment_methods_chart" class="h-350px"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expense Summary and Top Transactions -->
                <div class="row g-5 g-xl-8 mb-6">
                    <!-- Expense Summary -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-800">{{__('payments.expense_summary')}}</span>
                                    <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.deductions_and_refunds')}}</span>
                                </h3>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack mb-7">
                                    <span class="text-gray-800 fw-bold">{{__('payments.discounts_given')}}</span>
                                    <span class="text-gray-800 fw-bolder">{{ number_format($expenseSummary['discounts'], 2) }}</span>
                                </div>
                                <div class="progress h-6px bg-light mb-7">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ ($expenseSummary['discounts'] / max($financialSummary->total_sales ?? 1, 1)) * 100 }}%"></div>
                                </div>

                                <div class="d-flex flex-stack mb-7">
                                    <span class="text-gray-800 fw-bold">{{__('payments.refunds')}}</span>
                                    <span class="text-gray-800 fw-bolder">{{ number_format($expenseSummary['refunds'], 2) }}</span>
                                </div>
                                <div class="progress h-6px bg-light mb-7">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: {{ ($expenseSummary['refunds'] / max($financialSummary->total_sales ?? 1, 1)) * 100 }}%"></div>
                                </div>

                                <div class="d-flex flex-stack mb-7">
                                    <span class="text-gray-800 fw-bold">{{__('payments.tax_collected')}}</span>
                                    <span class="text-gray-800 fw-bolder">{{ number_format($expenseSummary['tax_collected'], 2) }}</span>
                                </div>
                                <div class="progress h-6px bg-light mb-7">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($expenseSummary['tax_collected'] / max($financialSummary->total_sales ?? 1, 1)) * 100 }}%"></div>
                                </div>

                                <div class="d-flex flex-stack">
                                    <span class="text-gray-800 fw-bold">{{__('payments.net_revenue')}}</span>
                                    <span class="text-gray-800 fw-bolder fs-4 text-success">
                                        {{ number_format((($financialSummary->total_sales ?? 0) / 100) - $expenseSummary['refunds'], 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Transactions -->
                    <div class="col-xl-8">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-800">{{__('payments.top_transactions')}}</span>
                                    <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.highest_value_orders')}}</span>
                                </h3>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                                        <thead>
                                            <tr class="fw-bold text-muted">
                                                <th class="min-w-150px">{{__('payments.order')}}</th>
                                                <th class="min-w-120px">{{__('payments.customer')}}</th>
                                                <th class="min-w-100px">{{__('payments.amount')}}</th>
                                                <th class="min-w-100px">{{__('payments.status')}}</th>
                                                <th class="min-w-120px">{{__('payments.time')}}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topTransactions as $order)
                                            <tr>
                                                <td>
                                                    <a href="#" class="text-gray-800 fw-bold text-hover-primary">#{{ $order->order_number }}</a>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600 fw-semibold">{{ $order->customer_name ?? __('payments.walk_in_customer') }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-bold">{{ number_format($order->total, 2) }}</span>
                                                </td>
                                                <td>
                                                    @php
                                                        $statusColor = [
                                                            'completed' => 'success',
                                                            'processing' => 'warning',
                                                            'confirmed' => 'info',
                                                            'draft' => 'secondary'
                                                        ][$order->status] ?? 'secondary';
                                                    @endphp
                                                    <span class="badge badge-light-{{ $statusColor }}">{{ $order->status }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600 fw-semibold">{{ $order->created_at->format('H:i') }}</span>
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

                <!-- Charts Script -->
                @push('scripts')
                <script>
                    "use strict";
                    var KTDashboardFinancial = function () {
                        var initHourlyChart = function() {
                            var element = document.getElementById('hourly_sales_chart');
                            if (!element) return;

                            var height = parseInt(KTUtil.css(element, 'height'));
                            var labelColor = KTUtil.getCssVariableValue('--kt-gray-500');
                            var borderColor = KTUtil.getCssVariableValue('--kt-gray-200');
                            var baseColor = KTUtil.getCssVariableValue('--kt-primary');
                            var lightColor = KTUtil.getCssVariableValue('--kt-primary-light');

                            var hours = [];
                            var sales = [];
                            
                            @foreach($hourlyBreakdown as $data)
                                hours.push('{{ $data->hour }}:00');
                                sales.push({{ $data->hourly_total }});
                            @endforeach

                            var options = {
                                series: [{
                                    name: '{{__('payments.sales')}}',
                                    data: sales
                                }],
                                chart: {
                                    fontFamily: 'inherit',
                                    type: 'area',
                                    height: height,
                                    toolbar: {
                                        show: false
                                    }
                                },
                                plotOptions: {},
                                dataLabels: {
                                    enabled: false
                                },
                                xaxis: {
                                    categories: hours,
                                    axisBorder: {
                                        show: false
                                    },
                                    axisTicks: {
                                        show: false
                                    },
                                    labels: {
                                        style: {
                                            colors: labelColor,
                                            fontSize: '12px'
                                        }
                                    }
                                },
                                yaxis: {
                                    labels: {
                                        style: {
                                            colors: labelColor,
                                            fontSize: '12px'
                                        }
                                    }
                                },
                                fill: {
                                    type: 'gradient',
                                    gradient: {
                                        shadeIntensity: 1,
                                        opacityFrom: 0.7,
                                        opacityTo: 0.3,
                                        stops: [0, 90, 100]
                                    }
                                },
                                stroke: {
                                    curve: 'smooth',
                                    width: 2
                                },
                                colors: [baseColor],
                                grid: {
                                    borderColor: borderColor,
                                    strokeDashArray: 4,
                                    yaxis: {
                                        lines: {
                                            show: true
                                        }
                                    }
                                },
                                tooltip: {
                                    y: {
                                        formatter: function(val) {
                                            return val.toFixed(2)
                                        }
                                    }
                                }
                            };

                            var chart = new ApexCharts(element, options);
                            chart.render();
                        };

                        var initPaymentMethodsChart = function() {
                            var element = document.getElementById('payment_methods_chart');
                            if (!element) return;

                            var height = parseInt(KTUtil.css(element, 'height'));
                            var labelColor = KTUtil.getCssVariableValue('--kt-gray-500');
                            
                            var methods = [];
                            var amounts = [];
                            var colors = ['#009EF7', '#50CD89', '#FFC700', '#F1416C', '#7239EA'];
                            
                            @foreach($paymentBreakdown as $index => $method)
                                methods.push('{{ $method->name }}');
                                amounts.push({{ $method->total_amount }});
                            @endforeach

                            var options = {
                                series: amounts,
                                chart: {
                                    fontFamily: 'inherit',
                                    type: 'donut',
                                    height: height
                                },
                                labels: methods,
                                colors: colors,
                                legend: {
                                    position: 'bottom',
                                    horizontalAlign: 'center',
                                    fontSize: '12px'
                                },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: '65%',
                                            labels: {
                                                show: true,
                                                total: {
                                                    show: true,
                                                    label: '{{__('payments.total')}}',
                                                    formatter: function(w) {
                                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toFixed(2);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                },
                                dataLabels: {
                                    enabled: false
                                },
                                stroke: {
                                    width: 3
                                },
                                tooltip: {
                                    y: {
                                        formatter: function(val) {
                                            return val.toFixed(2)
                                        }
                                    }
                                }
                            };

                            var chart = new ApexCharts(element, options);
                            chart.render();
                        };

                        return {
                            init: function () {
                                initHourlyChart();
                                initPaymentMethodsChart();
                            }
                        };
                    }();

                    KTUtil.onDOMContentLoaded(function () {
                        KTDrawers.init();
                        KTDashboardFinancial.init();
                    });
                </script>
                @endpush

            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>