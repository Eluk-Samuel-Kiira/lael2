@php
    $uomsById = $uoms->keyBy('id');
@endphp

<div class="modal fade" id="startProductionModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            {{-- ─── HEADER ────────────────────────────────────────────── --}}
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white d-flex align-items-center flex-wrap gap-2">
                    <i class="bi bi-play-circle-fill fs-3"></i>
                    <span>
                        {{ __('passwords.start_production') }}
                        <span class="text-white-50 mx-1">•</span>
                        <span class="fw-bold">{{ $order->production_number }}</span>
                    </span>
                    <span class="badge badge-light fw-bold">
                        <i class="bi bi-geo-alt me-1"></i>
                        {{ $order->location->name ?? __('pagination._none') }}
                    </span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- ─── BODY ──────────────────────────────────────────────── --}}
            <div class="modal-body scroll-y" style="max-height: 70vh; overflow-y: auto;">

                {{-- Info banner --}}
                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-6 p-4">
                    <i class="bi bi-info-circle fs-2 text-warning me-4"></i>
                    <div class="d-flex flex-stack flex-grow-1">
                        <div class="fw-semibold">
                            <h6 class="text-gray-900 fw-bold mb-1">
                                {{ __('passwords.start_production_info') }}
                            </h6>
                            <div class="fs-7 text-gray-700">
                                {{ __('passwords.start_production_info_text') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── INPUT MATERIALS ────────────────────────────────── --}}
                <div class="card card-flush bg-light-danger mb-5">
                    <div class="card-header min-h-50px">
                        <h3 class="card-title fs-6 fw-bold text-gray-800">
                            <i class="bi bi-box-arrow-in-down text-danger me-2 fs-4"></i>
                            {{ __('passwords.input_materials') }}
                            <span class="badge badge-light-danger ms-2">{{ $order->inputs->count() }}</span>
                        </h3>
                    </div>
                    <div class="card-body pt-3">
                        @foreach($order->inputs as $input)
                            @php
                                $variant     = $input->productVariant;
                                $uom         = $uomsById[$input->unit] ?? null;
                                $unitLabel   = $uom->name ?? ($uom->symbol ?? $input->unit);
                                $available   = $input->available_quantity;
                                $needed      = (float) $input->planned_quantity;
                                $isAvailable = $available >= $needed;
                                $source      = $input->availability_source;
                                $pct         = $needed > 0 ? min(100, ($available / $needed) * 100) : 0;
                            @endphp
                            <div class="d-flex align-items-start justify-content-between py-3 {{ !$loop->last ? 'border-bottom border-gray-300 border-dashed' : '' }}">
                                <div class="d-flex flex-column me-4">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fw-bold text-gray-800">
                                            {{ $variant->name ?? 'N/A' }}
                                        </span>

                                        @if($input->purchase_receipt_item_id && $input->purchaseReceiptItem)
                                            <span class="badge badge-light-info fs-8">
                                                <i class="bi bi-upc-scan me-1"></i>
                                                {{ $input->purchaseReceiptItem->batch_number }}
                                            </span>
                                        @elseif($source === 'batch_fifo')
                                            <span class="badge badge-light-primary fs-8">
                                                <i class="bi bi-stack me-1"></i>
                                                {{ __('passwords.all_batches') }}
                                            </span>
                                        @elseif($source === 'serial')
                                            <span class="badge badge-light-warning fs-8">
                                                <i class="bi bi-hash me-1"></i>
                                                {{ __('passwords.serial_numbers') }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Progress bar --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="progress w-100px h-5px">
                                            <div class="progress-bar {{ $isAvailable ? 'bg-success' : 'bg-danger' }}"
                                                 role="progressbar"
                                                 style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="text-muted fs-7">
                                            {{ number_format($available, 2) }} / {{ number_format($needed, 2) }} {{ $unitLabel }}
                                        </span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    @if(!$isAvailable)
                                        <span class="badge badge-light-danger">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            {{ __('passwords.insufficient_stock') }}
                                        </span>
                                    @else
                                        <span class="badge badge-light-success">
                                            <i class="bi bi-check-circle me-1"></i>
                                            {{ __('passwords.available') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ─── PAYMENT / PRODUCTION INFO ──────────────────────── --}}
                <div class="card card-flush bg-light-primary mb-5">
                    <div class="card-header min-h-50px">
                        <h3 class="card-title fs-6 fw-bold text-gray-800">
                            <i class="bi bi-credit-card text-primary me-2 fs-4"></i>
                            {{ $order->estimated_cost > 0
                                ? __('payments.payment_information')
                                : __('passwords.production_information') }}
                        </h3>
                    </div>
                    <div class="card-body pt-3">

                        @if($order->estimated_cost > 0)
                            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-5 p-3">
                                <i class="bi bi-exclamation-triangle fs-3 text-warning me-3"></i>
                                <div class="fs-7 text-gray-700 fw-semibold">
                                    {{ __('passwords.start_production_payment_info') }}
                                </div>
                            </div>
                        @else
                            <div class="notice d-flex bg-light-success rounded border-success border border-dashed mb-5 p-3">
                                <i class="bi bi-check-circle fs-3 text-success me-3"></i>
                                <div class="fs-7 text-gray-700 fw-semibold">
                                    {{ __('passwords.start_production_no_payment_info') }}
                                </div>
                            </div>
                        @endif

                        <form id="startProductionForm{{ $order->id }}">
                            @csrf

                            @if($order->estimated_cost > 0)
                                <div class="row g-4">
                                    <div class="col-md-7">
                                        <label class="form-label required fw-semibold">
                                            {{ __('payments.payment_method') }}
                                        </label>
                                        <select name="payment_method_id" class="form-select form-select-solid" required>
                                            <option value="">{{ __('payments.select_payment_method') }}</option>
                                            @if(isset($paymentMethods) && $paymentMethods->count() > 0)
                                                @foreach($paymentMethods as $method)
                                                    <option value="{{ $method->id }}">
                                                        {{ $method->name }}
                                                        @if($method->account_number)
                                                            — {{ $method->account_number }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="" disabled>
                                                    {{ __('payments.no_payment_methods_available') }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label required fw-semibold">
                                            {{ __('passwords.production_cost') }}
                                        </label>
                                        <div class="input-group input-group-solid">
                                            <span class="input-group-text">{{ currency_symbol() }}</span>
                                            <input type="number"
                                                   name="withdrawal_amount"
                                                   class="form-control"
                                                   value="{{ number_format($order->estimated_cost, 2, '.', '') }}"
                                                   min="0.01"
                                                   step="0.01"
                                                   required>
                                        </div>
                                        <div class="form-text text-muted">
                                            {{ __('passwords.estimated_cost') }}:
                                            <strong>{{ currency_symbol() }}{{ number_format($order->estimated_cost, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <input type="hidden" name="payment_method_id" value="">
                                <input type="hidden" name="withdrawal_amount" value="0">
                                <div class="d-flex align-items-center bg-light-success rounded p-4">
                                    <i class="bi bi-check-circle fs-2 text-success me-3"></i>
                                    <span class="fw-semibold text-gray-800">
                                        {{ __('passwords.no_payment_required') }}
                                    </span>
                                </div>
                            @endif

                            <div class="mt-5">
                                <label class="form-label fw-semibold">{{ __('passwords.notes') }}</label>
                                <textarea name="notes" class="form-control form-control-solid" rows="2"
                                          placeholder="{{ __('passwords.start_production_notes') }}"></textarea>
                            </div>

                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <input type="hidden" name="estimated_cost" value="{{ $order->estimated_cost }}">
                        </form>
                    </div>
                </div>

            </div>

            {{-- ─── FOOTER ────────────────────────────────────────────── --}}
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                </button>
                <button type="button" class="btn btn-warning" onclick="startProductionWithPayment({{ $order->id }})">
                    <i class="bi bi-play-fill me-2"></i>
                    <span class="indicator-label">
                        {{ __('passwords.start_production') }}
                        @if($order->estimated_cost > 0)
                            <span class="badge bg-white text-warning ms-2">
                                {{ currency_symbol() }}{{ number_format($order->estimated_cost, 2) }}
                            </span>
                        @endif
                    </span>
                    <span class="indicator-progress">
                        {{ __('passwords.processing') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>


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
                                        @php
                                            $uom       = $uomsById[$input->unit] ?? null;
                                            $unitLabel = $uom->name ?? ($uom->symbol ?? $input->unit);
                                        @endphp
                                        <tr>
                                            <td>{{ $input->productVariant->name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($input->planned_quantity, 2) }}</td>
                                            <td class="text-end">{{ number_format($input->actual_quantity, 2) }}</td>
                                            <td class="text-end">{{ $unitLabel }}</td>
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
                                        @php
                                            $uom       = $uomsById[$output->unit] ?? null;
                                            $unitLabel = $uom->name ?? ($uom->symbol ?? $output->unit);
                                        @endphp
                                        <tr>
                                            <td>{{ $output->productVariant->name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($output->planned_quantity, 2) }}</td>
                                            <td class="text-end">{{ number_format($output->actual_quantity, 2) }}</td>
                                            <td class="text-end">{{ number_format($output->defective_quantity, 2) }}</td>
                                            <td class="text-end">{{ $unitLabel }}</td>
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