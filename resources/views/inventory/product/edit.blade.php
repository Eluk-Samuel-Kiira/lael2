 
<div class="modal fade" id="editProduct{{ $product->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth._edit')}} {{__('pagination._product')}} - {{ $product->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_product_form{{ $product->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._product')}} </span>
                                </label>
                                <input type="text" value="{{ $product->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $product->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._sku')}} </span>
                                </label>
                                <input type="text" value="{{ $product->sku }}" class="form-control form-control-solid" name="sku" />
                                <div id="sku{{ $product->id }}"></div>
                            </div>
                        </div>

                        
                        <div class="row g-9 mb-8">
                            <div class="mb-10 fv-row col-md-4">
                                <x-typable-select 
                                    name="category_id"
                                    label="pagination._category"
                                    :options="$sub_categories"
                                    selected="{{ $product->category_id }}"
                                    placeholder="Type or select category..."
                                />
                                <div id="category_id{{ $product->id }}"></div>
                            </div>
                            
                            <div class="mb-10 fv-row col-md-4">
                                <label class="required form-label">{{__('pagination._type')}}</span></label>
                                <select name="type" class="form-select" >
                                    <option></option>
                                    <option value="physical" {{ $product->type == 'physical' ? 'selected' : '' }}>{{__('pagination._physical')}}</option>
                                    <option value="digital" {{ $product->type == 'digital' ? 'selected' : '' }}>{{__('pagination._digital')}}</option>
                                    <option value="service" {{ $product->type == 'service' ? 'selected' : '' }}>{{__('pagination._service')}}</option>
                                    <option value="composite" {{ $product->type == 'composite' ? 'selected' : '' }}>{{__('pagination._composite')}}</option>
                                </select>
                                <div id="type{{ $product->id }}"></div>
                            </div>

                            <div class="mb-10 fv-row col-md-4">
                                <label class="required form-label">{{__('pagination.inventory_strategy')}}</label>
                                <select name="inventory_strategy" class="form-select">
                                    <option></option>
                                    <option value="quantity" {{ $product->inventory_strategy == 'quantity' ? 'selected' : '' }}>{{__('pagination.quantity')}}</option>
                                    <option value="batch" {{ $product->inventory_strategy == 'batch' ? 'selected' : '' }}>{{__('pagination.batch')}}</option>
                                    <option value="serial" {{ $product->inventory_strategy == 'serial' ? 'selected' : '' }}>{{__('pagination.serial')}}</option>
                                    <option value="recipe" {{ $product->inventory_strategy == 'recipe' ? 'selected' : '' }}>{{__('pagination.recipe')}}</option>
                                </select>
                                <div id="inventory_strategy{{ $product->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._description')}} </span>
                                </label>
                                <textarea class="form-control form-control-solid" name="description" id="">{!! $product->description !!}</textarea>
                                <div id="description{{ $product->id }}"></div>
                            </div>
                        </div>

                        <button type="button" id="closeModalEditButton{{$product->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editProductInstanceLoop({{$product->id }})" id="editProductButton{{ $product->id }}" type="button" class="btn btn-primary" id>
                            <span class="indicator-label">{{__('auth._update')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>  

<script>
    async function editProductInstanceLoop(uniqueId) {
        const form         = document.getElementById('kt_modal_product_form' + uniqueId);
        const submitButton = document.getElementById('editProductButton' + uniqueId);
        const modalEl      = document.getElementById('editProduct' + uniqueId);
        if (!form || !submitButton) return;

        // ── 1. Collect form data ────────────────────────────────
        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';

        // Drop empty strings so Laravel validates against null, not ""
        Object.keys(data).forEach(k => {
            if (data[k] === '' || data[k] === 'undefined') delete data[k];
        });

        // ── 2. Loading state ────────────────────────────────────
        setEditModalLoading(submitButton, true);
        clearEditFormErrors(form);

        try {
            // ── 3. Submit ──────────────────────────────────────
            const url = '{{ route('products.update', ['product' => ':id']) }}'.replace(':id', uniqueId);

            const res = await fetch(url, {
                method: 'POST',   // _method=PUT inside body
                headers: {
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams(data),
            });

            const json = await res.json().catch(() => ({}));

            // ── 4. Validation errors (422) ─────────────────────
            if (res.status === 422) {
                if (json.errors) {
                    Object.entries(json.errors).forEach(([field, messages]) => {
                        showEditFieldError(form, uniqueId, field, messages[0]);
                    });
                }
                window.toastr?.error?.(json.message || 'Please fix the highlighted fields.');
                return;
            }

            if (!res.ok || json.success === false) {
                throw new Error(json.message || 'Request failed');
            }

            // ── 5. Success ─────────────────────────────────────
            window.toastr?.success?.(json.message || 'Product updated.');

            bootstrap.Modal.getInstance(modalEl)?.hide();

            // Refresh the table
            window.ProductList?.reload?.();

            // Refresh the drawer if it's still showing this product
            if (window.ProductDrawer?.isOpenFor?.(uniqueId)) {
                window.ProductDrawer.reload(uniqueId);
            }

        } catch (err) {
            console.error('[editProduct] failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
        } finally {
            setEditModalLoading(submitButton, false);
        }
    }

    // ── Helpers ─────────────────────────────────────────────
    function setEditModalLoading(button, busy) {
        if (!button) return;
        const label    = button.querySelector('.indicator-label');
        const progress = button.querySelector('.indicator-progress');
        button.disabled = busy;
        if (label)    label.style.display    = busy ? 'none' : '';
        if (progress) progress.style.display = busy ? 'inline-flex' : 'none';
    }

    function clearEditFormErrors(form) {
        if (!form) return;
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    }

    function showEditFieldError(form, productId, fieldName, message) {
        if (!form) return;

        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        field.classList.add('is-invalid');

        // Reuse the `<div id="fieldName{productId}">` holder if present
        let holder = form.querySelector(`#${fieldName}${productId}`);

        if (!holder) {
            holder = document.createElement('div');
            holder.id = `${fieldName}${productId}`;
            field.parentNode.appendChild(holder);
        }

        holder.classList.add('invalid-feedback', 'd-block');
        holder.textContent = message;
    }
</script>



