
{{-- Add this modal for updating output quantities --}}
<div class="modal fade" id="updateOutputModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2 text-warning"></i>
                    {{ __('passwords.update_actual_output') }} - {{ $order->production_number }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateOutputForm{{ $order->id }}" class="form">
                    @csrf
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="bi bi-info-circle fs-2 me-3"></i>
                        <div>
                            {{ __('passwords.enter_actual_output_instruction') }}
                        </div>
                    </div>

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
                                        <small class="text-muted">Planned: {{ number_format($planned, 2) }} {{ $output->unit }}</small>
                                    </div>
                                    <span class="badge badge-light-primary">
                                        {{ $output->inventory_strategy }}
                                    </span>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            {{ __('passwords.actual_quantity') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               name="outputs[{{ $output->id }}][actual_quantity]" 
                                               class="form-control actual-quantity-input"
                                               min="0" 
                                               step="0.01"
                                               value="{{ $actual > 0 ? $actual : '' }}"
                                               placeholder="Enter actual produced"
                                               data-output-id="{{ $output->id }}"
                                               data-planned="{{ $planned }}">
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
                                               placeholder="Enter defective units"
                                               data-output-id="{{ $output->id }}">
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between text-muted fs-7">
                                            <span>Remaining to produce: <span class="fw-bold remaining-display" data-output-id="{{ $output->id }}">{{ number_format(max(0, $planned - $actual), 2) }}</span></span>
                                            <span>Total: <span class="fw-bold total-display" data-output-id="{{ $output->id }}">{{ number_format($actual, 2) }}</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="border-top pt-4 mt-4">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                {{ __('passwords.cancel') }}
                            </button>
                            <button type="button" class="btn btn-warning" onclick="updateActualOutput({{ $order->id }})">
                                <i class="bi bi-save me-2"></i>
                                {{ __('passwords.save_actual_output') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    {{-- Add to the scripts section --}}
function updateActualOutput(orderId) {
    const form = document.getElementById(`updateOutputForm${orderId}`);
    if (!form) return;
    
    const submitButton = form.querySelector('button[type="button"].btn-warning');
    const formData = new FormData(form);
    const data = {};
    
    // Collect all output data
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    // Validate - check if at least one output has actual quantity
    let hasQuantity = false;
    document.querySelectorAll(`#updateOutputForm${orderId} .actual-quantity-input`).forEach(input => {
        if (parseFloat(input.value) > 0) {
            hasQuantity = true;
        }
    });
    
    if (!hasQuantity) {
        Swal.fire({
            title: '{{ __("passwords.validation_error") }}',
            text: '{{ __("passwords.enter_at_least_one_actual_quantity") }}',
            icon: 'warning',
            confirmButtonColor: '#0d6efd'
        });
        return;
    }
    
    // Show loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> {{ __("passwords.saving") }}...';
    
    // Build payload
    const payload = {
        outputs: []
    };
    
    document.querySelectorAll(`#updateOutputForm${orderId} .actual-quantity-input`).forEach(input => {
        const outputId = input.dataset.outputId;
        const actualQuantity = parseFloat(input.value) || 0;
        const defectiveQuantity = parseFloat(input.closest('.row').querySelector('.defective-quantity-input')?.value) || 0;
        
        if (actualQuantity > 0 || defectiveQuantity > 0) {
            payload.outputs.push({
                output_id: outputId,
                actual_quantity: actualQuantity,
                defective_quantity: defectiveQuantity
            });
        }
    });
    
    fetch(`/production-orders/${orderId}/update-output`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="bi bi-save me-2"></i> {{ __("passwords.save_actual_output") }}';
        
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById(`updateOutputModal${orderId}`));
            if (modal) modal.hide();
            
            Swal.fire({
                title: '{{ __("passwords.success") }}',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: '{{ __("passwords.error") }}',
                text: data.message || '{{ __("passwords.failed_to_update_output") }}',
                icon: 'error',
                confirmButtonColor: '#0d6efd'
            });
        }
    })
    .catch(error => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="bi bi-save me-2"></i> {{ __("passwords.save_actual_output") }}';
        
        console.error('Error:', error);
        Swal.fire({
            title: '{{ __("passwords.error") }}',
            text: '{{ __("passwords.failed_to_update_output") }}',
            icon: 'error',
            confirmButtonColor: '#0d6efd'
        });
    });
}

// Real-time validation and display update
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('input', function(e) {
        const target = e.target;
        if (target.classList.contains('actual-quantity-input')) {
            const outputId = target.dataset.outputId;
            const planned = parseFloat(target.dataset.planned) || 0;
            const actual = parseFloat(target.value) || 0;
            
            // Update remaining display
            const remainingDisplay = document.querySelector(`.remaining-display[data-output-id="${outputId}"]`);
            if (remainingDisplay) {
                remainingDisplay.textContent = Math.max(0, planned - actual).toFixed(2);
            }
            
            // Update total display
            const totalDisplay = document.querySelector(`.total-display[data-output-id="${outputId}"]`);
            if (totalDisplay) {
                totalDisplay.textContent = actual.toFixed(2);
            }
        }
    });
});
</script>