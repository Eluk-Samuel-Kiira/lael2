@extends('layouts.app')

@section('title', __('auth.order_summary_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- Toolbar --}}
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0 w-100">
                        <div class="page-title d-flex flex-column align-items-start text-start me-auto">
                            <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                                {{ __('auth.order_summary_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="osExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export_to_excel') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="osPrintBtn">
                                <i class="bi bi-printer fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.print') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="row mb-6">
                    <div class="col-12"><div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <form id="osFilterForm" class="row g-3" onsubmit="return false;">
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('accounting.start_date') }}</label>
                                    <input type="date" class="form-control" id="osStartDate" value="{{ $startDate }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('accounting.end_date') }}</label>
                                    <input type="date" class="form-control" id="osEndDate" value="{{ $endDate }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('auth.location') }}</label>
                                    <select class="form-select" id="osLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                        <option value=""></option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('auth.department') }}</label>
                                    <select class="form-select" id="osDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                        <option value=""></option>
                                        @foreach($departments as $dep)
                                            <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('auth.order_type') }}</label>
                                    <select class="form-select" id="osType">
                                        <option value="">{{ __('auth.all_types') }}</option>
                                        <option value="sale"   @selected($orderType === 'sale')>{{ __('auth.sale') }}</option>
                                        <option value="return" @selected($orderType === 'return')>{{ __('auth.return') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('auth.order_status') }}</label>
                                    <select class="form-select" id="osStatus">
                                        <option value="">{{ __('auth.all_statuses') }}</option>
                                        <option value="draft"     @selected($orderStatus === 'draft')>{{ __('auth.draft') }}</option>
                                        <option value="confirmed" @selected($orderStatus === 'confirmed')>{{ __('auth.confirmed') }}</option>
                                        <option value="completed" @selected($orderStatus === 'completed')>{{ __('auth.completed') }}</option>
                                        <option value="cancelled" @selected($orderStatus === 'cancelled')>{{ __('auth.cancelled') }}</option>
                                    </select>
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-primary" id="osApplyBtn">
                                        <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                    </button>
                                    <button type="button" class="btn btn-light" id="osClearBtn">
                                        <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div></div>
                </div>

                {{-- SKELETON --}}
                <div id="osSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="os-sk os-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="os-sk os-sk-text mb-2" style="width:60%;"></div>
                                    <div class="os-sk os-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 3; $i++)
                        <div class="col-md-4"><div class="card"><div class="card-body"><div class="os-sk os-sk-text" style="height:60px;"></div></div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="os-sk os-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="os-sk os-sk-text mb-2" style="width:35%;"></div>
                                <div class="os-sk os-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="os-sk os-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="osContent" class="d-none">

                    {{-- Empty --}}
                    <div id="osEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6 mb-1">{{ __('auth.no_orders_found_for_period') }}</p>
                        <p class="text-muted fs-7">{{ __('auth.try_adjusting_date_range_or_filters') }}</p>
                    </div></div>

                    {{-- KPI cards --}}
                    <div id="osKpiCards" class="row g-6 mb-6"></div>

                    {{-- Payment summary --}}
                    <div id="osPaymentCards" class="row g-6 mb-6"></div>

                    {{-- Status breakdown --}}
                    <div id="osStatusCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.order_status_breakdown') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="osStatusBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.status') }}</th>
                                    <th class="text-center">{{ __('auth.order_count') }}</th>
                                    <th class="text-end">{{ __('accounting.total_amount') }}</th>
                                    <th class="text-end">{{ __('accounting.average_amount') }}</th>
                                    <th class="pe-4 text-center" style="width:30%;">{{ __('accounting.distribution') }}</th>
                                </tr></thead>
                                <tbody id="osStatusBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Type breakdown --}}
                    <div id="osTypeCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-tags fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.order_type_breakdown') }}</h3>
                                </div>
                                <span class="badge badge-light-info fs-7" id="osTypeBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.type') }}</th>
                                    <th class="text-center">{{ __('auth.order_count') }}</th>
                                    <th class="text-end">{{ __('accounting.total_amount') }}</th>
                                    <th class="text-end">{{ __('accounting.average_amount') }}</th>
                                    <th class="pe-4 text-center" style="width:30%;">{{ __('accounting.distribution') }}</th>
                                </tr></thead>
                                <tbody id="osTypeBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Daily breakdown --}}
                    <div id="osDailyCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-calendar3 fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.daily_breakdown') }}</h3>
                                </div>
                                <span class="badge badge-light-success fs-7" id="osDailyBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="osDailyTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.date') }}</th>
                                    <th>{{ __('accounting.day') }}</th>
                                    <th class="text-center">{{ __('auth.orders') }}</th>
                                    <th class="text-end">{{ __('auth.daily_total') }}</th>
                                    <th class="text-end">{{ __('auth.daily_tax') }}</th>
                                    <th class="text-end">{{ __('auth.daily_discount') }}</th>
                                    <th class="text-end pe-4">{{ __('auth.avg_order_value') }}</th>
                                </tr></thead>
                                <tbody id="osDailyBody"></tbody>
                                <tfoot class="bg-light" id="osDailyFoot"></tfoot>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="osPaginationWrap">
                                <span class="text-muted fs-7" id="osPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="osPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Hourly breakdown --}}
                    <div id="osHourlyCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.hourly_order_distribution') }}</h3>
                                </div>
                                <span class="badge badge-light-info fs-7" id="osHourlyBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.hour') }}</th>
                                    <th>{{ __('accounting.period') }}</th>
                                    <th class="text-center">{{ __('auth.orders') }}</th>
                                    <th class="text-end">{{ __('auth.hourly_total') }}</th>
                                    <th class="text-end pe-4">{{ __('auth.avg_order_value') }}</th>
                                </tr></thead>
                                <tbody id="osHourlyBody"></tbody>
                                <tfoot class="bg-light" id="osHourlyFoot"></tfoot>
                            </table></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .os-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: os-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .os-sk-text   { height: 12px; border-radius: 4px; }
    .os-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .os-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes os-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #osSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script>
    window.OS_ROUTES = { data: '{{ route("reports.orders.summary-data") }}' };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.OS_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        orderType:    @json($orderType),
        orderStatus:  @json($orderStatus),
        perPage:      @json($perPage),
    };
    window.OS_I18N = {
        totalOrders:        @json(__('auth.total_orders')),
        totalSales:         @json(__('auth.total_sales')),
        totalTax:           @json(__('auth.total_tax')),
        totalDiscount:      @json(__('auth.total_discount')),
        avgOrderValue:      @json(__('auth.avg_order_value')),
        highestOrder:       @json(__('auth.highest_order')),
        lowestOrder:        @json(__('auth.lowest_order')),
        completed:          @json(__('auth.completed')),
        confirmed:          @json(__('auth.confirmed')),
        cancelled:          @json(__('auth.cancelled')),
        draft:              @json(__('auth.draft')),

        totalPaid:          @json(__('auth.total_paid')),
        outstandingBalance: @json(__('auth.outstanding_balance')),
        completedOrders:    @json(__('auth.completed_orders')),
        ofTotalSales:       @json(__('auth.of_total_sales')),
        completionRate:     @json(__('auth.completion_rate')),

        orderCount:         @json(__('auth.order_count')),
        orders:             @json(__('auth.orders')),
        days:               @json(__('auth.days')),
        hours:              @json(__('auth.hours')),
        totalAverage:       @json(__('accounting.total_average')),
        total:              @json(__('accounting.total')),
        hour:               @json(__('accounting.hour')),
        period:             @json(__('accounting.period')),
        day:                @json(__('accounting.day')),
        date:               @json(__('accounting.date')),

        dailyTotal:         @json(__('auth.daily_total')),
        dailyTax:           @json(__('auth.daily_tax')),
        dailyDiscount:      @json(__('auth.daily_discount')),
        hourlyTotal:        @json(__('auth.hourly_total')),
    };
</script>
@include('reports.orders.summary-scripts')
@endpush