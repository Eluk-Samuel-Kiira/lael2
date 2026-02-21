<!-- App Settings Modal -->
<div class="modal fade" id="appSettingsTenant{{$tenant->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('payments.application_settings')}} - {{ $tenant->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                @if($tenant->appSettings)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-5">
                                <div class="card-header">
                                    <h6 class="card-title fw-bold">{{ __('payments.basic_information') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-column gap-3">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-muted">{{ __('payments.app_name') }}:</span>
                                            <span class="fw-bold">{{ $tenant->appSettings->app_name }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-muted">{{ __('payments.email') }}:</span>
                                            <span class="fw-bold">{{ $tenant->appSettings->app_email }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-muted">{{ __('payments.contact') }}:</span>
                                            <span class="fw-bold">{{ $tenant->appSettings->app_contact }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-muted">{{ __('payments.currency') }}:</span>
                                            <span class="fw-bold">{{ $tenant->appSettings->currency }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-muted">{{ __('payments.locale') }}:</span>
                                            <span class="fw-bold">{{ $tenant->appSettings->locale }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-5">
                                <div class="card-header">
                                    <h6 class="card-title fw-bold">{{ __('payments.license_information') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-column gap-3">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-muted">{{ __('payments.license_type') }}:</span>
                                            <span class="badge badge-light-{{ $tenant->appSettings->license_type == 'enterprise' ? 'primary' : ($tenant->appSettings->license_type == 'premium' ? 'success' : 'info') }}">
                                                {{ ucfirst($tenant->appSettings->license_type) }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-muted">{{ __('payments.license_status') }}:</span>
                                            @if($tenant->appSettings->license_active)
                                                <span class="badge badge-light-success">{{ __('payments.active') }}</span>
                                            @else
                                                <span class="badge badge-light-danger">{{ __('payments.inactive') }}</span>
                                            @endif
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-muted">{{ __('payments.public_key') }}:</span>
                                            <code class="text-truncate" style="max-width: 150px;">{{ $tenant->appSettings->public_key }}</code>
                                        </div>
                                        @if($tenant->appSettings->license_expires_at)
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-semibold text-muted">{{ __('payments.expires_at') }}:</span>
                                                <span class="fw-bold">{{ \Carbon\Carbon::parse($tenant->appSettings->license_expires_at)->format('d M Y') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title fw-bold">{{ __('payments.limits_features') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3 d-flex justify-content-between">
                                                <span class="fw-semibold text-muted">{{ __('payments.max_users') }}:</span>
                                                <span class="fw-bold">{{ $tenant->appSettings->max_users ?? __('payments.unlimited') }}</span>
                                            </div>
                                            <div class="mb-3 d-flex justify-content-between">
                                                <span class="fw-semibold text-muted">{{ __('payments.max_products') }}:</span>
                                                <span class="fw-bold">{{ $tenant->appSettings->max_products ?? __('payments.unlimited') }}</span>
                                            </div>
                                            <div class="mb-3 d-flex justify-content-between">
                                                <span class="fw-semibold text-muted">{{ __('payments.max_departments') }}:</span>
                                                <span class="fw-bold">{{ $tenant->appSettings->max_departments ?? __('payments.unlimited') }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3 d-flex justify-content-between">
                                                <span class="fw-semibold text-muted">{{ __('payments.max_categories') }}:</span>
                                                <span class="fw-bold">{{ $tenant->appSettings->max_categories ?? __('payments.unlimited') }}</span>
                                            </div>
                                            <div class="mb-3 d-flex justify-content-between">
                                                <span class="fw-semibold text-muted">{{ __('payments.max_suppliers') }}:</span>
                                                <span class="fw-bold">{{ $tenant->appSettings->max_suppliers ?? __('payments.unlimited') }}</span>
                                            </div>
                                            <div class="mb-3 d-flex justify-content-between">
                                                <span class="fw-semibold text-muted">{{ __('payments.storage_limit') }}:</span>
                                                <span class="fw-bold">{{ $tenant->appSettings->storage_limit_mb ?? __('payments.unlimited') }} MB</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <span class="fw-semibold text-muted">{{ __('payments.features') }}:</span>
                                                <div class="mt-2">
                                                    @if($tenant->appSettings->enable_inventory)
                                                        <span class="badge badge-light-success me-2">{{ __('payments.inventory') }}</span>
                                                    @endif
                                                    @if($tenant->appSettings->enable_multi_location)
                                                        <span class="badge badge-light-success me-2">{{ __('payments.multi_location') }}</span>
                                                    @endif
                                                    @if($tenant->appSettings->enable_reports)
                                                        <span class="badge badge-light-success me-2">{{ __('payments.reports') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle fs-1 me-2"></i>
                        {{ __('payments.no_app_settings') }}
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.close') }}</button>
            </div>
        </div>
    </div>
</div>