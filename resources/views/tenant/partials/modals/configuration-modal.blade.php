
<!-- Configuration Modal -->
<div class="modal fade" id="configTenant{{$tenant->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('payments.configuration')}} - {{ $tenant->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                @if($tenant->configuration)
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gy-4 gs-9">
                            <tbody>
                                <tr>
                                    <th class="fw-bold text-nowrap bg-light" style="width: 200px;">{{ __('payments.currency_code') }}</th>
                                    <td>
                                        <span class="badge badge-light-primary fs-6 px-4 py-2">{{ $tenant->configuration->currency_code }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="fw-bold bg-light">{{ __('payments.timezone') }}</th>
                                    <td class="fw-semibold">{{ $tenant->configuration->timezone }}</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold bg-light">{{ __('payments.locale') }}</th>
                                    <td class="fw-semibold">{{ $tenant->configuration->locale }}</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold bg-light">{{ __('payments.fiscal_year_start') }}</th>
                                    <td class="fw-semibold">{{ \Carbon\Carbon::parse($tenant->configuration->fiscal_year_start)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold bg-light">{{ __('payments.tax_calculation_method') }}</th>
                                    <td>
                                        <span class="badge badge-light-{{ $tenant->configuration->tax_calculation_method == 'exclusive' ? 'success' : 'info' }} fs-6 px-4 py-2">
                                            {{ $tenant->configuration->tax_calculation_method == 'exclusive' ? __('payments.exclusive') : __('payments.inclusive') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle fs-1 me-2"></i>
                        {{ __('payments.no_configuration') }}
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.close') }}</button>
            </div>
        </div>
    </div>
</div>