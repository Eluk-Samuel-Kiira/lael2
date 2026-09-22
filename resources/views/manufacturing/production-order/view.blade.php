
<div class="modal fade" id="viewProduction{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="bi bi-eye me-2"></i>
                    {{ __('passwords.production_order') }} - {{ $order->production_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Order Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="fw-bold">{{ __('passwords.production_number') }}:</td>
                                <td>{{ $order->production_number }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('auth._status') }}:</td>
                                <td><span class="badge badge-{{ $order->status_badge }}">{{ $order->status_label }}</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('passwords.scheduled_date') }}:</td>
                                <td>{{ $order->scheduled_date ? $order->scheduled_date->format('M d, Y H:i') : 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="fw-bold">{{ __('passwords.location') }}:</td>
                                <td>{{ $order->location->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('auth._creater') }}:</td>
                                <td>{{ $order->createdBy->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">{{ __('passwords.total_cost') }}:</td>
                                <td>{{ number_format($order->total_cost ?? 0, 2) }} {{ currency_symbol() }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Input Materials -->
                <div class="card card-flush bg-light-danger mb-3">
                    <div class="card-header">
                        <h6 class="card-title">
                            <i class="bi bi-box-arrow-in-down me-2 text-danger"></i>
                            {{ __('passwords.input_materials') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('passwords.material') }}</th>
                                        <th class="text-end">{{ __('passwords.planned') }}</th>
                                        <th class="text-end">{{ __('passwords.actual') }}</th>
                                        <th class="text-end">{{ __('passwords.unit') }}</th>
                                        <th class="text-end">{{ __('passwords.cost') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->inputs as $input)
                                        <tr>
                                            <td>{{ $input->productVariant->name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($input->planned_quantity, 2) }}</td>
                                            <td class="text-end">{{ number_format($input->actual_quantity, 2) }}</td>
                                            <td class="text-end">{{ $input->unit }}</td>
                                            <td class="text-end">{{ number_format($input->actual_cost ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Output Products -->
                <div class="card card-flush bg-light-success">
                    <div class="card-header">
                        <h6 class="card-title">
                            <i class="bi bi-box-arrow-out me-2 text-success"></i>
                            {{ __('passwords.output_products') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('passwords.product') }}</th>
                                        <th class="text-end">{{ __('passwords.planned') }}</th>
                                        <th class="text-end">{{ __('passwords.actual') }}</th>
                                        <th class="text-end">{{ __('passwords.defective') }}</th>
                                        <th class="text-end">{{ __('passwords.unit') }}</th>
                                        <th class="text-end">{{ __('passwords.strategy') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->outputs as $output)
                                        <tr>
                                            <td>{{ $output->productVariant->name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($output->planned_quantity, 2) }}</td>
                                            <td class="text-end">{{ number_format($output->actual_quantity, 2) }}</td>
                                            <td class="text-end">{{ number_format($output->defective_quantity, 2) }}</td>
                                            <td class="text-end">{{ $output->unit }}</td>
                                            <td class="text-end"><span class="badge badge-light-primary">{{ $output->inventory_strategy }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>{{ __('auth._close') }}
                </button>
            </div>
        </div>
    </div>
</div>