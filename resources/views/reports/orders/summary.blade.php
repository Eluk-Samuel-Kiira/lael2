{{-- resources/views/reports/orders/summary.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.order_summary_report'))

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
                                {{ __('auth.order_summary_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.summary') }}</li>
                            </ul>
                        </div>
                        @if($summary->total_orders > 0)
                        <div class="dropdown">
                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportTableToExcel('orderSummaryTable', 'order_summary')">{{ __('accounting.export_to_excel') }}</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportTableToCSV('orderSummaryTable', 'order_summary')">{{ __('accounting.export_to_csv') }}</a></li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Filter Section --}}
                <div class="card mb-6">
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.orders.summary') }}" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" name="location_id">
                                    <option value="">{{ __('auth.all_locations') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" name="department_id">
                                    <option value="">{{ __('auth.all_departments') }}</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ $departmentId == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.order_type') }}</label>
                                <select class="form-select" name="order_type">
                                    <option value="">{{ __('auth.all_types') }}</option>
                                    <option value="dine_in" {{ $orderType == 'dine_in' ? 'selected' : '' }}>Dine In</option>
                                    <option value="takeaway" {{ $orderType == 'takeaway' ? 'selected' : '' }}>Takeaway</option>
                                    <option value="delivery" {{ $orderType == 'delivery' ? 'selected' : '' }}>Delivery</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.order_status') }}</label>
                                <select class="form-select" name="order_status">
                                    <option value="">{{ __('auth.all_statuses') }}</option>
                                    <option value="pending" {{ $orderStatus == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="confirmed" {{ $orderStatus == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                    <option value="processing" {{ $orderStatus == 'processing' ? 'selected' : '' }}>Processing</option>
                                    <option value="completed" {{ $orderStatus == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ $orderStatus == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">{{ __('accounting.apply_filters') }}</button>
                                <a href="{{ route('reports.orders.summary') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Summary Statistics --}}
                @if($summary->total_orders > 0)
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-primary">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ $summary->total_orders }}</div>
                                <div class="text-muted">{{ __('auth.total_orders') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-success">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ currency_symbol() }}{{ number_format($summary->total_sales, 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_sales') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-warning">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ currency_symbol() }}{{ number_format($summary->total_tax, 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_tax') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-info">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ currency_symbol() }}{{ number_format($summary->total_discount, 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_discount') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-danger">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ currency_symbol() }}{{ number_format($summary->average_order_value, 2) }}</div>
                                <div class="text-muted">{{ __('auth.average_order_value') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-secondary">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ currency_symbol() }}{{ number_format($summary->max_order_value, 2) }}</div>
                                <div class="text-muted">{{ __('auth.max_order_value') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status Breakdown Table --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('auth.order_status_breakdown') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.status') }}</th>
                                        <th class="text-center">{{ __('auth.order_count') }}</th>
                                        <th class="text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.average_amount') }}</th>
                                        <th class="text-center">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statusBreakdown as $status)
                                    @php
                                        $percentage = $summary->total_orders > 0 ? ($status->count / $summary->total_orders) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $status->color }}">
                                                {{ ucfirst($status->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $status->count }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($status->total_amount, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($status->average_amount, 2) }}</td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-end">
                                                <div class="progress w-100 me-2" style="height: 6px; max-width: 100px;">
                                                    <div class="progress-bar bg-{{ $status->color }}" style="width: {{ $percentage }}%;"></div>
                                                </div>
                                                <span class="fw-bold min-w-45px text-end">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Type Breakdown Table --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('auth.order_type_breakdown') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.type') }}</th>
                                        <th class="text-center">{{ __('auth.order_count') }}</th>
                                        <th class="text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.average_amount') }}</th>
                                        <th class="text-center">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($typeBreakdown as $type)
                                    @php
                                        $percentage = $summary->total_orders > 0 ? ($type->count / $summary->total_orders) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $type->color }}">
                                                {{ ucfirst($type->type) }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $type->count }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($type->total_amount, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($type->average_amount, 2) }}</td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-end">
                                                <div class="progress w-100 me-2" style="height: 6px; max-width: 100px;">
                                                    <div class="progress-bar bg-{{ $type->color }}" style="width: {{ $percentage }}%;"></div>
                                                </div>
                                                <span class="fw-bold min-w-45px text-end">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Daily Breakdown Table --}}
                @if($dailyBreakdown->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('auth.daily_breakdown') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="orderSummaryTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.date') }}</th>
                                        <th class="text-center">{{ __('auth.order_count') }}</th>
                                        <th class="text-end">{{ __('auth.daily_total') }}</th>
                                        <th class="text-end">{{ __('auth.daily_average') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyBreakdown as $day)
                                    <tr>
                                        <td>{{ $day->date }}</td>
                                        <td class="text-center">{{ $day->order_count }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($day->daily_total, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($day->daily_average, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                @else
                {{-- No Data Message --}}
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_orders_found_for_period') }}</p>
                        </div>
                    </div>
                </div>
                @endif
                
            </div>
        </div>
    </div>
</div>

<script>
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(table);
    XLSX.utils.book_append_sheet(wb, ws, filename);
    XLSX.writeFile(wb, filename + '.xlsx');
}
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => col.innerText);
        csv.push(rowData.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '.csv';
    link.click();
}
</script>
@endsection