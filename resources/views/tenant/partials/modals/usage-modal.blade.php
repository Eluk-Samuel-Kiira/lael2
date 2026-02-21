
<!-- Usage Modal -->
<div class="modal fade" id="usageTenant{{$tenant->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('payments.usage_tracking')}} - {{ $tenant->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                @if($tenant->usageTracking && $tenant->usageTracking->count() > 0)
                    <!-- Summary Cards -->
                    @if($tenant->latestUsage)
                        <div class="row g-5 g-xl-8 mb-5">
                            <div class="col-xl-3">
                                <div class="card bg-light-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-3">
                                                <span class="symbol-label bg-primary bg-opacity-15">
                                                    <i class="ki-duotone ki-shop fs-2x text-primary"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fs-6 fw-semibold text-gray-500">{{ __('payments.current_shops') }}</div>
                                                <div class="fw-bold fs-2 text-gray-800">{{ $tenant->latestUsage->current_shops }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3">
                                <div class="card bg-light-success">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-3">
                                                <span class="symbol-label bg-success bg-opacity-15">
                                                    <i class="ki-duotone ki-people fs-2x text-success"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fs-6 fw-semibold text-gray-500">{{ __('payments.current_users') }}</div>
                                                <div class="fw-bold fs-2 text-gray-800">{{ $tenant->latestUsage->current_users }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3">
                                <div class="card bg-light-warning">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-3">
                                                <span class="symbol-label bg-warning bg-opacity-15">
                                                    <i class="ki-duotone ki-box fs-2x text-warning"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fs-6 fw-semibold text-gray-500">{{ __('payments.current_products') }}</div>
                                                <div class="fw-bold fs-2 text-gray-800">{{ number_format($tenant->latestUsage->current_products) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3">
                                <div class="card bg-light-danger">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-3">
                                                <span class="symbol-label bg-danger bg-opacity-15">
                                                    <i class="ki-duotone ki-chart-simple fs-2x text-danger"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fs-6 fw-semibold text-gray-500">{{ __('payments.monthly_sales') }}</div>
                                                <div class="fw-bold fs-2 text-gray-800">{{ number_format($tenant->latestUsage->monthly_sales_count) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Usage History Table -->
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gy-4 gs-9">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800">
                                    <th>{{ __('payments.date') }}</th>
                                    <th>{{ __('payments.shops_locations') }}</th>
                                    <th>{{ __('payments.users') }}</th>
                                    <th>{{ __('payments.products') }}</th>
                                    <th>{{ __('payments.customers') }}</th>
                                    <th>{{ __('payments.api_calls') }}</th>
                                    <th>{{ __('payments.storage_mb') }}</th>
                                    <th>{{ __('payments.response_time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tenant->usageTracking as $usage)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($usage->tracking_date)->format('d M Y') }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold">{{ $usage->current_shops }} {{ __('payments.shops') }}</span>
                                                <span class="text-muted fs-7">{{ $usage->current_locations }} {{ __('payments.locations') }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $usage->current_users }}</td>
                                        <td>{{ number_format($usage->current_products) }}</td>
                                        <td>{{ number_format($usage->current_customers) }}</td>
                                        <td>{{ number_format($usage->monthly_api_calls) }}</td>
                                        <td>{{ number_format($usage->monthly_storage_mb, 2) }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ $usage->average_response_time_ms < 200 ? 'success' : ($usage->average_response_time_ms < 500 ? 'warning' : 'danger') }}">
                                                {{ $usage->average_response_time_ms }} ms
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle fs-1 me-2"></i>
                        {{ __('payments.no_usage_data') }}
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.close') }}</button>
            </div>
        </div>
    </div>
</div>