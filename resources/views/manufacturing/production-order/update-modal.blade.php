{{-- resources/views/manufacturing/production-order/start-modal.blade.php --}}
<!-- START PRODUCTION MODAL -->
<div class="modal fade" id="startProductionModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">
                    <i class="bi bi-play-fill me-2"></i>
                    {{ __('passwords.start_production') }} - {{ $order->production_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center">
                    <i class="bi bi-info-circle fs-2 me-3"></i>
                    <div>
                        <strong>{{ __('passwords.start_production_info') }}</strong><br>
                        {{ __('passwords.start_production_info_text') }}
                    </div>
                </div>

                <!-- Input Summary -->
                <div class="card card-flush bg-light-danger mb-4">
                    <div class="card-header">
                        <h6 class="card-title">
                            <i class="bi bi-box-arrow-in-down me-2 text-danger"></i>
                            {{ __('passwords.input_materials') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        @foreach($order->inputs as $input)
                            @php
                                $variant = $input->productVariant;
                                $available = $variant->overal_quantity_at_hand ?? 0;
                                $needed = $input->planned_quantity;
                                $isAvailable = $available >= $needed;
                            @endphp
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>{{ $variant->name ?? 'N/A' }}</span>
                                <span>
                                    <span class="fw-bold {{ $isAvailable ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($available, 2) }}
                                    </span>
                                    / {{ number_format($needed, 2) }} {{ $input->unit }}
                                    @if(!$isAvailable)
                                        <span class="badge badge-danger ms-2">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            {{ __('passwords.insufficient_stock') }}
                                        </span>
                                    @else
                                        <span class="badge badge-success ms-2">
                                            <i class="bi bi-check-circle me-1"></i>
                                            {{ __('passwords.available') }}
                                        </span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- ✅ ALWAYS SHOW FORM - Even when cost is 0 -->
                <div class="card card-flush bg-light-primary mb-4">
                    <div class="card-header">
                        <h6 class="card-title">
                            <i class="bi bi-credit-card me-2 text-primary"></i>
                            {{ $order->estimated_cost > 0 ? __('payments.payment_information') : __('passwords.production_information') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($order->estimated_cost > 0)
                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle me-2"></i>
                                {{ __('passwords.start_production_payment_info') }}
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                {{ __('passwords.start_production_no_payment_info') }}
                            </div>
                        @endif

                        {{-- ✅ FORM IS ALWAYS PRESENT --}}
                        <form id="startProductionForm{{ $order->id }}">
                            @csrf
                            
                            @if($order->estimated_cost > 0)
                                <div class="mb-3">
                                    <label class="form-label required">{{ __('payments.payment_method') }}</label>
                                    <select name="payment_method_id" class="form-select" required>
                                        <option value="">{{ __('payments.select_payment_method') }}</option>
                                        @if(isset($paymentMethods) && $paymentMethods->count() > 0)
                                            @foreach($paymentMethods as $method)
                                                <option value="{{ $method->id }}">
                                                    {{ $method->name }} 
                                                    ({{ $method->account_number ?? 'N/A' }})
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>No payment methods available</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label required">{{ __('passwords.production_cost') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ currency_symbol() }}</span>
                                        <input type="number" 
                                               name="withdrawal_amount" 
                                               class="form-control" 
                                               value="{{ number_format($order->estimated_cost, 2) }}" 
                                               min="0.01" 
                                               step="0.01"
                                               required>
                                    </div>
                                    <small class="text-muted">{{ __('passwords.estimated_cost') }}: {{ currency_symbol() }}{{ number_format($order->estimated_cost, 2) }}</small>
                                </div>
                            @else
                                {{-- ✅ Hidden fields when cost is 0 --}}
                                <input type="hidden" name="payment_method_id" value="">
                                <input type="hidden" name="withdrawal_amount" value="0">
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    {{ __('passwords.no_payment_required') }}
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">{{ __('passwords.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" 
                                    placeholder="{{ __('passwords.start_production_notes') }}"></textarea>
                            </div>

                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <input type="hidden" name="estimated_cost" value="{{ $order->estimated_cost }}">
                        </form>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between border-top pt-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                    </button>
                    <button type="button" class="btn btn-warning" onclick="startProductionWithPayment({{ $order->id }})">
                        <i class="bi bi-play-fill me-2"></i>
                        {{ __('passwords.start_production') }}
                        @if($order->estimated_cost > 0)
                            <span class="badge bg-light text-dark ms-2">
                                {{ currency_symbol() }}{{ number_format($order->estimated_cost, 2) }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



{{-- resources/views/manufacturing/production-order/complete-modal.blade.php --}}
<!-- COMPLETE PRODUCTION MODAL -->
<div class="modal fade" id="completeProductionModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ __('passwords.complete_production') }} - {{ $order->production_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center">
                    <i class="bi bi-info-circle fs-2 me-3"></i>
                    <div>
                        {{ __('passwords.complete_production_instruction') }}
                    </div>
                </div>

                <form id="completeProductionForm{{ $order->id }}">
                    @csrf
                    @foreach($order->outputs as $index => $output)
                        @php
                            $variant = $output->productVariant;
                            $planned = $output->planned_quantity;
                            $actual = $output->actual_quantity;
                            $defective = $output->defective_quantity;
                        @endphp
                        <div class="card card-dashed mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-0">{{ $variant->name ?? 'Product' }}</h6>
                                        <small class="text-muted">
                                            {{ __('passwords.planned') }}: {{ number_format($planned, 2) }} {{ $output->unit }}
                                            @if($actual > 0)
                                                <span class="badge badge-success ms-2">
                                                    {{ __('passwords.produced_so_far') }}: {{ number_format($actual, 2) }}
                                                </span>
                                            @endif
                                        </small>
                                    </div>
                                    <span class="badge badge-light-primary">
                                        {{ $output->inventory_strategy }}
                                    </span>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            {{ __('passwords.actual_quantity') }}
                                            @if($loop->first)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <input type="number" 
                                               name="outputs[{{ $output->id }}][actual_quantity]" 
                                               class="form-control actual-quantity-input"
                                               min="0" 
                                               step="0.01"
                                               value="{{ $actual > 0 ? $actual : '' }}"
                                               placeholder="{{ __('passwords.enter_actual_produced') }}"
                                               data-output-id="{{ $output->id }}"
                                               data-planned="{{ $planned }}"
                                               {{ $loop->first ? 'required' : '' }}>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            {{ __('passwords.defective_quantity') }}
                                        </label>
                                        <input type="number" 
                                               name="outputs[{{ $output->id }}][defective_quantity]" 
                                               class="form-control defective-quantity-input"
                                               min="0" 
                                               step="0.01"
                                               value="{{ $defective > 0 ? $defective : '' }}"
                                               placeholder="{{ __('passwords.enter_defective_units') }}"
                                               data-output-id="{{ $output->id }}">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between text-muted fs-7 mt-2">
                                    <span>
                                        {{ __('passwords.remaining') }}: 
                                        <span class="fw-bold remaining-display" data-output-id="{{ $output->id }}">
                                            {{ number_format(max(0, $planned - $actual), 2) }}
                                        </span>
                                    </span>
                                    <span>
                                        {{ __('passwords.total_produced') }}: 
                                        <span class="fw-bold total-display" data-output-id="{{ $output->id }}">
                                            {{ number_format($actual, 2) }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </form>

                <div class="d-flex justify-content-between border-top pt-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                    </button>
                    <button type="button" class="btn btn-success" onclick="completeProductionWithOutputs({{ $order->id }})">
                        <i class="bi bi-check-circle me-2"></i>
                        {{ __('passwords.complete_production') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- resources/views/manufacturing/production-order/view-modal.blade.php --}}
<!-- VIEW PRODUCTION MODAL -->
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