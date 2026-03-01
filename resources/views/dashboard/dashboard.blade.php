<x-app-layout>
    @section('title', __('payments.dashboard'))
    @section('content')
        
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('payments.general_dashboard')}}</h1>
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
                    <li class="breadcrumb-item text-muted">{{__('payments.general_dashboard')}}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Stats Cards Row -->
                <div class="row g-5 g-xl-8 mb-6">
                    <!-- Today's Sales -->
                    <div class="col-xl-3">
                        <div class="card card-xl-stretch mb-5 mb-xl-8">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-duotone ki-dollar fs-2x text-primary">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                            </i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-600 fw-bold fs-6">{{__('payments.today_sales')}}</span>
                                        <span class="fw-bolder fs-2x text-gray-900">{{ number_format($todayStats['sales'], 2) }}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">
                                            @if($salesChange > 0)
                                                <span class="text-success">+{{ number_format($salesChange, 1) }}%</span>
                                            @else
                                                <span class="text-danger">{{ number_format($salesChange, 1) }}%</span>
                                            @endif
                                            {{__('payments.vs_yesterday')}}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Orders -->
                    <div class="col-xl-3">
                        <div class="card card-xl-stretch mb-5 mb-xl-8">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-success">
                                            <i class="ki-duotone ki-basket fs-2x text-success">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                            </i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-600 fw-bold fs-6">{{__('payments.today_orders')}}</span>
                                        <span class="fw-bolder fs-2x text-gray-900">{{ $todayStats['orders'] }}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">{{ $todayStats['customers'] }} {{__('payments.unique_customers')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Profit -->
                    <div class="col-xl-3">
                        <div class="card card-xl-stretch mb-5 mb-xl-8">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-warning">
                                            <i class="ki-duotone ki-chart-line-down fs-2x text-warning">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-600 fw-bold fs-6">{{__('payments.today_profit')}}</span>
                                        <span class="fw-bolder fs-2x text-gray-900">{{ number_format($todayStats['profit'], 2) }}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">{{ number_format(($todayStats['profit'] / max($todayStats['sales'], 1)) * 100, 1) }}% {{__('payments.margin')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Users -->
                    <div class="col-xl-3">
                        <div class="card card-xl-stretch mb-5 mb-xl-8">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-info">
                                            <i class="ki-duotone ki-people fs-2x text-info">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                            </i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-600 fw-bold fs-6">{{__('payments.active_users')}}</span>
                                        <span class="fw-bolder fs-2x text-gray-900">{{ $activeUsers }}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">{{__('payments.currently_online')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-5 g-xl-8 mb-6">
                    <!-- Weekly Sales Chart -->
                    <div class="col-xl-8">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-800">{{__('payments.weekly_sales_trend')}}</span>
                                    <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.last_7_days')}}</span>
                                </h3>
                                <div class="card-toolbar">
                                    <button class="btn btn-sm btn-light" id="weekly_sales_refresh">
                                        <i class="ki-duotone ki-arrow-circle-right fs-2"></i>{{__('payments.refresh')}}
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="weekly_sales_chart" class="h-350px"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-800">{{__('payments.best_selling_products')}}</span>
                                    <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.last_30_days')}}</span>
                                </h3>
                            </div>
                            <div class="card-body pt-0">
                                @foreach($bestSellers as $index => $product)
                                <div class="d-flex flex-stack mb-5">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-40px me-3">
                                            <div class="symbol-label fs-6 fw-bold bg-light-{{ ['primary', 'success', 'warning', 'info', 'danger'][$index % 5] }} text-{{ ['primary', 'success', 'warning', 'info', 'danger'][$index % 5] }}">
                                                {{ $index + 1 }}
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold fs-6">{{ $product->name }}</span>
                                            <span class="text-gray-400 fw-semibold fs-7">{{ $product->sku }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-end">
                                        <span class="text-gray-800 fw-bold">{{ $product->total_quantity }} {{__('payments.units')}}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">{{ number_format($product->total_revenue, 2) }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row -->
                <div class="row g-5 g-xl-8 mb-6">
                    <!-- Top Categories -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-800">{{__('payments.top_categories')}}</span>
                                    <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.by_revenue')}}</span>
                                </h3>
                            </div>
                            <div class="card-body pt-0">
                                @foreach($topCategories as $category)
                                <div class="d-flex flex-stack mb-5">
                                    <span class="text-gray-800 fw-bold">{{ $category->name }}</span>
                                    <div class="d-flex align-items-center">
                                        <span class="text-gray-800 fw-bold me-2">{{ number_format($category->total_revenue, 2) }}</span>
                                        <span class="badge badge-light-success">{{ $category->total_quantity }} {{__('payments.units')}}</span>
                                    </div>
                                </div>
                                <div class="progress h-6px bg-light mb-7">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: {{ ($category->total_revenue / $topCategories->max('total_revenue')) * 100 }}%"></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Alerts -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-800">{{__('payments.inventory_alerts')}}</span>
                                    <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.low_stock_items')}}</span>
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-danger">{{ $outOfStockItems }} {{__('payments.out_of_stock')}}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @forelse($lowStockItems as $item)
                                <div class="d-flex flex-stack mb-5">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-40px me-3">
                                            <div class="symbol-label bg-light-danger">
                                                <i class="ki-duotone ki-information fs-2 text-danger"></i>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold fs-6">{{ $item->name }}</span>
                                            <span class="text-gray-400 fw-semibold fs-7">{{__('payments.stock_left')}}: {{ $item->overal_quantity_at_hand }}</span>
                                        </div>
                                    </div>
                                    <span class="badge badge-light-warning">{{__('payments.low_stock')}}</span>
                                </div>
                                @empty
                                <div class="text-center py-10">
                                    <i class="ki-duotone ki-check-circle fs-3x text-success mb-3"></i>
                                    <p class="text-gray-600 fw-semibold fs-6">{{__('payments.no_low_stock_items')}}</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold text-gray-800">{{__('payments.recent_orders')}}</span>
                                    <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.last_10_orders')}}</span>
                                </h3>
                                <div class="card-toolbar">
                                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-light">{{__('payments.view_all')}}</a>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @foreach($recentOrders as $order)
                                <div class="d-flex flex-stack mb-5">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-40px me-3">
                                            <div class="symbol-label bg-light-{{ $order->status == 'completed' ? 'success' : ($order->status == 'processing' ? 'warning' : 'info') }}">
                                                <i class="ki-duotone ki-{{ $order->status == 'completed' ? 'tick' : 'timer' }} fs-2 text-{{ $order->status == 'completed' ? 'success' : ($order->status == 'processing' ? 'warning' : 'info') }}"></i>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold fs-6">#{{ $order->order_number }}</span>
                                            <span class="text-gray-400 fw-semibold fs-7">{{ $order->customer_name ?? __('payments.walk_in_customer') }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-end">
                                        <span class="text-gray-800 fw-bold">{{ number_format($order->total, 2) }}</span>
                                        <span class="text-gray-400 fw-semibold fs-7">{{ $order->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Active Users Table -->
                    <div class="row g-5 g-xl-8 mb-6">
                        <div class="col-xl-12">
                            <div class="card card-flush">
                                <div class="card-header pt-5">
                                    <h3 class="card-title align-items-start flex-column">
                                        <span class="card-label fw-bold text-gray-800">{{__('payments.active_users_list')}}</span>
                                        <span class="text-gray-400 mt-1 fw-semibold fs-6">{{__('payments.users_logged_in_last_15_min')}}</span>
                                    </h3>
                                    <div class="card-toolbar">
                                        <span class="badge badge-light-success fs-base">{{ $activeUsers }} {{__('payments.active')}}</span>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                                            <thead>
                                                <tr class="fw-bold text-muted">
                                                    <th class="min-w-200px">{{__('payments.user')}}</th>
                                                    <th class="min-w-150px">{{__('payments.location_dept')}}</th>
                                                    <th class="min-w-150px">{{__('payments.device_browser')}}</th>
                                                    <th class="min-w-150px">{{__('payments.ip_address')}}</th>
                                                    <th class="min-w-150px">{{__('payments.last_activity')}}</th>
                                                    <th class="min-w-100px">{{__('payments.status')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($activeUsersList as $session)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-45px me-3">
                                                                @if($session->profile_image)
                                                                    <img src="{{ asset('storage/' . $session->profile_image) }}" alt="">
                                                                @else
                                                                    <div class="symbol-label bg-light-primary text-primary fw-bold">
                                                                        {{ substr($session->first_name, 0, 1) }}{{ substr($session->last_name, 0, 1) }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="d-flex flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $session->full_name }}</span>
                                                                <span class="text-gray-400 fw-semibold fs-7">{{ $session->job_title ?? __('payments.staff') }}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600 fw-semibold">
                                                            {{ $session->location_name ?? '—' }} / {{ $session->department_name ?? '—' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="text-gray-800 fw-bold">
                                                                <i class="fas {{ $session->device_icon }} me-1"></i> {{ $session->device }}
                                                            </span>
                                                            <span class="text-gray-400 fw-semibold fs-7">{{ $session->browser }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600 fw-semibold">{{ $session->ip_address }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600 fw-semibold">{{ $session->last_seen }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-success">{{__('payments.online')}}</span>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-10">
                                                        <i class="ki-duotone ki-information fs-3x text-muted mb-3"></i>
                                                        <p class="text-gray-600 fw-semibold fs-6">{{__('payments.no_active_users')}}</p>
                                                    </td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Script -->
                @push('scripts')
                <script>
                    "use strict";
                    var KTWidgets = function () {
                        var initWeeklySalesChart = function() {
                            var element = document.getElementById('weekly_sales_chart');
                            if (!element) return;

                            var height = parseInt(KTUtil.css(element, 'height'));
                            var labelColor = KTUtil.getCssVariableValue('--kt-gray-500');
                            var borderColor = KTUtil.getCssVariableValue('--kt-gray-200');
                            var baseColor = KTUtil.getCssVariableValue('--kt-primary');
                            var lightColor = KTUtil.getCssVariableValue('--kt-primary-light');

                            var options = {
                                series: [{
                                    name: '{{__('payments.sales')}}',
                                    data: [{{ $weeklySales->pluck('total_sales')->implode(', ') }}]
                                }],
                                chart: {
                                    fontFamily: 'inherit',
                                    type: 'bar',
                                    height: height,
                                    toolbar: {
                                        show: false
                                    }
                                },
                                plotOptions: {
                                    bar: {
                                        borderRadius: 4,
                                        horizontal: false,
                                        columnWidth: ['40%']
                                    }
                                },
                                dataLabels: {
                                    enabled: false
                                },
                                xaxis: {
                                    categories: [{{ $weeklySales->pluck('day_name')->map(function($day) { return '"' . $day . '"'; })->implode(', ') }}],
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
                                    opacity: 1
                                },
                                states: {
                                    normal: {
                                        filter: {
                                            type: 'none',
                                            value: 0
                                        }
                                    },
                                    hover: {
                                        filter: {
                                            type: 'none',
                                            value: 0
                                        }
                                    },
                                    active: {
                                        allowMultipleDataPointsSelection: false,
                                        filter: {
                                            type: 'none',
                                            value: 0
                                        }
                                    }
                                },
                                tooltip: {
                                    style: {
                                        fontSize: '12px'
                                    },
                                    y: {
                                        formatter: function (val) {
                                            return val.toFixed(2)
                                        }
                                    }
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
                                }
                            };

                            var chart = new ApexCharts(element, options);
                            chart.render();
                        };

                        return {
                            init: function () {
                                initWeeklySalesChart();
                            }
                        };
                    }();

                    KTUtil.onDOMContentLoaded(function () {
                        KTDrawers.init();
                        KTWidgets.init();
                    });
                </script>
                @endpush
                
            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>