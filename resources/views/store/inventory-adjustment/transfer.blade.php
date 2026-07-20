<!-- Edit Item Modal for Each Item (Transfer Modal) -->
<div class="modal fade" id="stockTransfer{{$item->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('passwords.transfer_to_loc_dept') }}
                    {{ $item->departmentItem->name ?? __('pagination._none') }} : 
                    {{ $item->variant->name ?? __('pagination._none') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="stockItemTransfer{{$item->id}}">
                    @csrf
                    @method('PUT')
                    
                    {{-- Hidden fields --}}
                    <input type="hidden" name="variant_id" value="{{ $item->variant_id }}">
                    
                    {{-- ✅ Error container for transfer --}}
                    <div id="transferError{{$item->id}}" class="alert alert-danger d-none" role="alert"></div>
                    
                    <div class="text-center pt-10">
                        <!-- Location & Department (Dependent Dropdown) -->
                        <div class="row g-9 mb-8">
                            <x-liveblade-dependent-dropdown 
                                id="location_department_transfer_{{ $item->id }}"
                                parentName="location_id"
                                childName="department_id"
                                parentLabel="pagination._location"
                                childLabel="auth._department"
                                :parentOptions="$locations"
                                :childOptions="$departments"
                                route="{{ route('get.departments') }}"
                                selectedParent="{{ $item->location_id }}"
                                selectedChild="{{ $item->department_id }}"
                                skipAjax="true"
                            />
                            <div id="location_id{{ $item->id }}"></div>
                            <div id="department_id{{ $item->id }}"></div>
                        </div>
                        
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-5">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">{{ __('passwords.current_stock_level') }}</label>
                                    {{-- ✅ This will be updated by JS --}}
                                    <input type="text" class="form-control text-center fs-3 fw-bold text-warning" 
                                           id="currentStock{{$item->id}}" 
                                           name="current_quantity"
                                           value="{{ $item->quantity_allocated }}" readonly>
                                </div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-7">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">{{ __('passwords.transfer_quantity') }}</label>
                                    <input type="number"
                                           class="form-control text-center fs-3 fw-bold text-gray-800 quantity-input"
                                           id="adjustAmount{{$item->id}}"
                                           name="adjust_amount"
                                           value="0"
                                           data-item-id="{{$item->id}}" 
                                           data-current="{{ $item->quantity_allocated }}" />
                                    <small class="text-muted">{{ __('passwords.transfer_hint') ?? 'Enter quantity to transfer' }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- Transfer Preview --}}
                        <div class="alert alert-primary d-flex align-items-center mt-3" role="alert">
                            <i class="bi bi-arrow-left-right fs-4 me-2"></i>
                            <div>
                                <span class="fw-bold">{{ __('passwords.transfer_summary') }}</span><br>
                                <span id="transferSummary{{$item->id}}">
                                    {{ __('passwords.no_transfer_yet') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="reset" class="btn btn-light me-3" id="closeTransferModalButton{{ $item->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                    @can('transfer stock')
                    <button onclick="updateInventoryTransfer({{$item->id}})" id="editInvTransferButton{{ $item->id }}" type="button" class="btn btn-primary">
                        <span class="indicator-label">{{__('auth._update')}}</span>
                        <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                    @endcan
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Mgt - Transfer Only -->
<script>
    
    // ── Transfer Stock ─────────────────────────────────────────
    function updateInventoryTransfer(uniqueId) {
        const submitButton = document.getElementById('editInvTransferButton' + uniqueId);
        LiveBlade.toggleButtonLoading(submitButton, true);

        var form = document.getElementById('stockItemTransfer' + uniqueId);
        var formData = new FormData(form);
        var data = Object.fromEntries(formData.entries());

        var updateUrl = '/transfer-stock/' + uniqueId;
        handleTransferStock(data, updateUrl, uniqueId, submitButton);
    }

    function handleTransferStock(data, updateUrl, uniqueId, submitButton) {
        LiveBlade.editLoopForms(data, updateUrl)
        .then(noErrorStatus => {
            if (noErrorStatus) {
                const closeButton = document.getElementById(`closeTransferModalButton${uniqueId}`);
                if (closeButton) closeButton.click();
            }
        })
        .catch(error => {
            console.error('An unexpected error occurred:', error);
        })
        .finally(() => {
            LiveBlade.toggleButtonLoading(submitButton, false);
        });
    }

    // ── Handle Transfer Input ──────────────────────────────────
    function handleTransferInput(e) {
        const input = e.target;
        const itemId = input.dataset.itemId;
        const current = parseInt(input.dataset.current) || 0;
        let adjust = parseInt(input.value) || 0;

        const errorEl = document.getElementById(`transferError${itemId}`);
        const currentStockEl = document.getElementById(`currentStock${itemId}`);
        const summaryEl = document.getElementById(`transferSummary${itemId}`);

        // Clear previous errors
        if (errorEl) {
            errorEl.style.display = 'none';
            errorEl.textContent = '';
        }
        input.classList.remove('is-invalid');

        // Validate transfer quantity
        if (adjust > current) {
            if (errorEl) {
                errorEl.style.display = 'block';
                errorEl.textContent = 'Cannot transfer more than available. Available: ' + current;
            }
            input.classList.add('is-invalid');
            input.value = current;
            adjust = current;
            toastr['warning']('Cannot transfer more than available');
        }

        if (adjust < 0) {
            if (errorEl) {
                errorEl.style.display = 'block';
                errorEl.textContent = 'Quantity cannot be negative';
            }
            input.classList.add('is-invalid');
            input.value = 0;
            adjust = 0;
            toastr['warning']('Quantity cannot be negative');
        }

        // Update preview
        const remaining = current - adjust;
        
        if (currentStockEl) {
            currentStockEl.value = remaining;
            // Color code
            if (remaining < 5) {
                currentStockEl.className = 'form-control text-center fs-3 fw-bold text-danger';
            } else if (remaining < 10) {
                currentStockEl.className = 'form-control text-center fs-3 fw-bold text-warning';
            } else {
                currentStockEl.className = 'form-control text-center fs-3 fw-bold text-success';
            }
        }

        // Update summary
        if (summaryEl) {
            if (adjust === 0) {
                summaryEl.textContent = 'No transfer has been configured yet';
                summaryEl.className = 'text-muted';
            } else {
                summaryEl.innerHTML = `
                    <span class="text-primary fw-bold">
                        Transfer: ${adjust} units
                    </span>
                    <span class="text-muted ms-2">
                        | Remaining: ${remaining} units
                    </span>
                `;
            }
        }
    }

    // ── Event Delegation for Transfer Inputs ──────────────────
    document.addEventListener('input', function(e) {
        if (e.target.classList && e.target.classList.contains('quantity-input')) {
            const transferForm = e.target.closest('#stockItemTransfer');
            if (transferForm) {
                handleTransferInput(e);
            }
        }
    });

    // ── Also trigger on change events ──────────────────────────
    document.addEventListener('change', function(e) {
        if (e.target.classList && e.target.classList.contains('quantity-input')) {
            const transferForm = e.target.closest('#stockItemTransfer');
            if (transferForm) {
                handleTransferInput(e);
            }
        }
    });

    // ── Debug: Log when modal opens ────────────────────────────
    document.addEventListener('shown.bs.modal', function(e) {
        if (e.target.id && e.target.id.startsWith('stockTransfer')) {
            console.log('Transfer modal opened:', e.target.id);
        }
    });

</script>