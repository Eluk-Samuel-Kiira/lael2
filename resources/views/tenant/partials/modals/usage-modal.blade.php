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
                    @if($tenant->latestUsage)
                        <!-- Responsive Summary Cards -->
                        <div class="row g-3 g-xl-8 mb-8">
                            <!-- Current Shops Card -->
                            <div class="col-6 col-sm-6 col-xl-3">
                                <div class="card bg-light-primary">
                                    <div class="card-body p-3 p-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px symbol-md-50px me-3">
                                                <span class="symbol-label bg-primary bg-opacity-15">
                                                    <i class="ki-duotone ki-shop fs-2x text-primary">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                    </i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fs-7 fs-md-6 fw-semibold text-gray-500">{{ __('payments.current_shops') }}</div>
                                                <div class="fw-bold fs-3 fs-md-2 text-gray-800">{{ $tenant->latestUsage->current_shops }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Current Users Card -->
                            <div class="col-6 col-sm-6 col-xl-3">
                                <div class="card bg-light-success">
                                    <div class="card-body p-3 p-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px symbol-md-50px me-3">
                                                <span class="symbol-label bg-success bg-opacity-15">
                                                    <i class="ki-duotone ki-people fs-2x text-success">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                    </i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fs-7 fs-md-6 fw-semibold text-gray-500">{{ __('payments.current_users') }}</div>
                                                <div class="fw-bold fs-3 fs-md-2 text-gray-800">{{ $tenant->latestUsage->current_users }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Current Products Card -->
                            <div class="col-6 col-sm-6 col-xl-3">
                                <div class="card bg-light-warning">
                                    <div class="card-body p-3 p-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px symbol-md-50px me-3">
                                                <span class="symbol-label bg-warning bg-opacity-15">
                                                    <i class="ki-duotone ki-box fs-2x text-warning">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                        <span class="path4"></span>
                                                    </i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fs-7 fs-md-6 fw-semibold text-gray-500">{{ __('payments.current_products') }}</div>
                                                <div class="fw-bold fs-3 fs-md-2 text-gray-800">{{ number_format($tenant->latestUsage->current_products) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Monthly Sales Card -->
                            <div class="col-6 col-sm-6 col-xl-3">
                                <div class="card bg-light-danger">
                                    <div class="card-body p-3 p-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px symbol-md-50px me-3">
                                                <span class="symbol-label bg-danger bg-opacity-15">
                                                    <i class="ki-duotone ki-chart-simple fs-2x text-danger">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                    </i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fs-7 fs-md-6 fw-semibold text-gray-500">{{ __('payments.monthly_sales') }}</div>
                                                <div class="fw-bold fs-3 fs-md-2 text-gray-800">{{ number_format($tenant->latestUsage->monthly_sales_count) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Usage History Table - Responsive -->
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gy-4 gs-9">
                            <thead>
                                <tr class="fw-bold fs-7 fs-md-6 text-gray-800">
                                    <th class="min-w-100px">{{ __('payments.date') }}</th>
                                    <th class="min-w-120px">{{ __('payments.shops_locations') }}</th>
                                    <th class="min-w-60px">{{ __('payments.users') }}</th>
                                    <th class="min-w-80px d-none d-md-table-cell">{{ __('payments.products') }}</th>
                                    <th class="min-w-80px d-none d-lg-table-cell">{{ __('payments.customers') }}</th>
                                    <th class="min-w-80px d-none d-xl-table-cell">{{ __('payments.api_calls') }}</th>
                                    <th class="min-w-80px d-none d-xl-table-cell">{{ __('payments.storage_mb') }}</th>
                                    <th class="min-w-100px">{{ __('payments.response_time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tenant->usageTracking as $usage)
                                    <tr>
                                        <td>
                                            <span class="fw-bold fs-7">{{ \Carbon\Carbon::parse($usage->tracking_date)->format('d M Y') }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold fs-7">{{ $usage->current_shops }} {{ __('payments.shops') }}</span>
                                                <span class="text-muted fs-8">{{ $usage->current_locations }} {{ __('payments.locations') }}</span>
                                            </div>
                                        </td>
                                        <td><span class="fw-bold fs-7">{{ $usage->current_users }}</span></td>
                                        <td class="d-none d-md-table-cell">{{ number_format($usage->current_products) }}</td>
                                        <td class="d-none d-lg-table-cell">{{ number_format($usage->current_customers) }}</td>
                                        <td class="d-none d-xl-table-cell">{{ number_format($usage->monthly_api_calls) }}</td>
                                        <td class="d-none d-xl-table-cell">{{ number_format($usage->monthly_storage_mb, 2) }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ $usage->average_response_time_ms < 200 ? 'success' : ($usage->average_response_time_ms < 500 ? 'warning' : 'danger') }} py-1 px-2 fs-8">
                                                {{ $usage->average_response_time_ms }} ms
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning d-flex align-items-center">
                        <i class="ki-duotone ki-information-5 fs-2x me-3">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div>{{ __('payments.no_usage_data') }}</div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.close') }}</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Responsive improvements for Usage Modal */
    @media (max-width: 767px) {
        .modal-body {
            padding: 1rem !important;
        }
        
        .symbol-40px {
            width: 35px;
            height: 35px;
        }
        
        .ki-duotone.fs-2x {
            font-size: 1.5rem !important;
        }
        
        .card-body {
            padding: 0.75rem !important;
        }
        
        .fs-3 {
            font-size: 1.2rem !important;
        }
        
        .fs-6 {
            font-size: 0.7rem !important;
        }
    }
    
    @media (max-width: 576px) {
        .row.g-3 {
            --bs-gutter-y: 0.6rem;
        }
        
        .symbol-40px {
            width: 30px;
            height: 30px;
        }
        
        .fw-bold.fs-3 {
            font-size: 1rem !important;
        }
        
        .badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }
    }
    
    /* Icon path styles */
    .ki-duotone .path1,
    .ki-duotone .path2,
    .ki-duotone .path3,
    .ki-duotone .path4 {
        opacity: 0.8;
    }
    
    .ki-duotone:hover .path1,
    .ki-duotone:hover .path2,
    .ki-duotone:hover .path3,
    .ki-duotone:hover .path4 {
        opacity: 1;
    }
</style>