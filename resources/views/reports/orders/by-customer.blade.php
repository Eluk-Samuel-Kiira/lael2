@extends('layouts.app')

@section('title', __('auth.sales_by_customer'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar --}}
                <div class="d-flex justify-content-between align-items-center mb-6">
                    <div>
                        <h1 class="fw-bold fs-1 my-0">{{ __('auth.sales_by_customer') }}</h1>
                        <ul class="breadcrumb fw-semibold fs-7 my-0 pt-1">
                            <li class="breadcrumb-item text-muted">
                                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                            </li>
                            <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                            <li class="breadcrumb-item text-muted">{{ __('auth.sales_by_customer') }}</li>
                        </ul>
                    </div>
                    @if($customerSales->count() > 0)
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportToExcel()">{{ __('accounting.export_to_excel') }}</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportToCSV()">{{ __('accounting.export_to_csv') }}</a></li>
                        </ul>
                    </div>
                    @endif
                </div>

                {{-- Filters --}}
                <div class="card mb-6">
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.orders.by-customer') }}" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" name="start_date" value="{{ $displayStartDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" name="end_date" value="{{ $displayEndDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" name="location_id">
                                    <option value="">{{ __('auth.all_locations') }}</option>
                                    @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" name="department_id">
                                    <option value="">{{ __('auth.all_departments') }}</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.min_spent') }}</label>
                                <input type="number" step="0.01" class="form-control" name="min_spent" value="{{ $minSpent }}" placeholder="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.max_spent') }}</label>
                                <input type="number" step="0.01" class="form-control" name="max_spent" value="{{ $maxSpent }}" placeholder="999999">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.min_orders') }}</label>
                                <input type="number" class="form-control" name="min_orders" value="{{ $minOrders }}" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.max_orders') }}</label>
                                <input type="number" class="form-control" name="max_orders" value="{{ $maxOrders }}" placeholder="999">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2"><i class="ki-duotone ki-filter fs-2"></i> {{ __('accounting.apply_filters') }}</button>
                                <a href="{{ route('reports.orders.by-customer') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Customer Segments Cards --}}
                @if($customerSales->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-info">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ $customerSegments->new }}</div>
                                <div class="text-muted">{{ __('auth.new_customers') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-primary">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ $customerSegments->returning }}</div>
                                <div class="text-muted">{{ __('auth.returning_customers') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-success">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ $customerSegments->regular }}</div>
                                <div class="text-muted">{{ __('auth.regular_customers') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-warning">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ $customerSegments->vip }}</div>
                                <div class="text-muted">{{ __('auth.vip_customers') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-lg-2">
                        <div class="card bg-light-secondary">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ $summary->total_customers }}</div>
                                <div class="text-muted">{{ __('auth.total_customers') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-lg-2">
                        <div class="card bg-light-danger">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold">{{ currency_symbol() }}{{ number_format($summary->average_per_customer, 2) }}</div>
                                <div class="text-muted">{{ __('accounting.average_per_customer') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top Customers Chart --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('auth.top_customers') }}</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="topCustomersChart" style="height: 400px;"></canvas>
                    </div>
                </div>

                {{-- Customer Sales Table --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('auth.customer_sales_report') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="customerSalesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('accounting.customer') }}</th>
                                        <th>{{ __('accounting.orders') }}</th>
                                        <th class="text-end">{{ __('auth.total_spent') }}</th>
                                        <th class="text-end">{{ __('accounting.average') }}</th>
                                        <th class="text-end">{{ __('auth.max_order') }}</th>
                                        <th>{{ __('accounting.last_order') }}</th>
                                        <th class="text-center">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customerSales as $index => $customer)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-35px symbol-circle me-2">
                                                    <div class="symbol-label bg-light-primary">
                                                        <i class="ki-duotone ki-user fs-2"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $customer->full_name }}</div>
                                                    <small class="text-muted">{{ $customer->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><span class="badge badge-light-primary">{{ $customer->order_count }}</span></td>
                                        <td class="text-end fw-bold text-success">{{ currency_symbol() }}{{ number_format($customer->total_spent, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($customer->average_order_value, 2) }}</td>
                                        <td class="text-end text-danger">{{ currency_symbol() }}{{ number_format($customer->max_order_value, 2) }}</td>
                                        <td>{{ $customer->last_order_date ? $customer->last_order_date->format('M d, Y') : '-' }}</td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ min($customer->percentage, 100) }}%;"></div>
                                                </div>
                                                <span class="fw-bold min-w-45px">{{ number_format($customer->percentage, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="3" class="text-end">{{ __('accounting.total') }}:</td>
                                        <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($summary->total_revenue, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($summary->average_per_customer, 2) }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @else
                <div class="card">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_customers_found_for_period') }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Top Customers Chart
    @if($topCustomers->count() > 0)
    const ctx = document.getElementById('topCustomersChart').getContext('2d');
    const topCustomersData = @json($topCustomers);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: topCustomersData.map(c => c.full_name.substring(0, 15) + (c.full_name.length > 15 ? '...' : '')),
            datasets: [{
                label: 'Total Sales ({{ currency_symbol() }})',
                data: topCustomersData.map(c => c.total_spent),
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '{{ currency_symbol() }}' + context.raw.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '{{ currency_symbol() }}' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    @endif

    function exportToExcel() {
        const table = document.getElementById('customerSalesTable');
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Customer Sales');
        XLSX.writeFile(wb, 'customer_sales_{{ date('Y_m_d') }}.xlsx');
    }
    
    function exportToCSV() {
        const table = document.getElementById('customerSalesTable');
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
        link.download = 'customer_sales_{{ date('Y_m_d') }}.csv';
        link.click();
    }
</script>
@endpush
@endsection