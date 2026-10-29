 
<div class="modal fade" id="kt_modal_add_promotion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_department">
                <h2 class="fw-bold">{{__('auth._create')}} {{__('pagination._promotion')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_promotion_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._promotion')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="name" />
                                <div id="name"></div>
                            </div>

                        </div>
                        <div class="row g-9 mb-8">
                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination.discount_type')}}</span></label>
                                <select name="discount_type" class="form-select">
                                    <option></option>
                                    <option value="percentage">{{__('pagination._percentage')}}</option>
                                    <option value="fixed_amount">{{__('pagination._fixed')}}</option>
                                    <!-- <option value="buy_x_get_y">{{__('pagination.buy_x_get_y')}}</option> -->
                                </select>
                                <div id="discount_type"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.discount_value')}}</span>
                                </label>
                                <input type="number" class="form-control form-control-solid" name="discount_value"/>
                                <div id="discount_value"></div>
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.start_date')}}</span>
                                </label>
                                <input type="date" class="form-control form-control-solid" name="start_date" />
                                <div id="start_date"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.end_date')}}</span>
                                </label>
                                <input type="date" class="form-control form-control-solid" name="end_date" />
                                <div id="end_date"></div>
                            </div>

                        </div>

                        <button type="reset" class="btn btn-light me-3" id="discardPromoButton" data-bs-dismiss="modal">{{__('auth._discard') }}</button>
                        
                        <button 
                            id="submitPromoButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitPromotionForm('kt_modal_add_promotion_form', 'submitPromoButton', '{{ route('promotion.store') }}', 'POST', 'discardPromoButton')">
                            
                            <span class="indicator-label">{{__('auth.submit')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>  


<script>
async function submitPromotionForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
    const form         = document.getElementById(formId);
    const submitButton = document.getElementById(submitButtonId);
    if (!form || !submitButton) return;

    const data = Object.fromEntries(new FormData(form));
    data._method = method;

    Object.keys(data).forEach(k => {
        if (data[k] === '' || data[k] === 'undefined') delete data[k];
    });

    setButtonLoading(submitButton, true);
    clearFormErrors(form);

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept':           'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams(data),
        });

        const json = await res.json().catch(() => ({}));

        if (res.status === 422) {
            Object.entries(json.errors || {}).forEach(([field, messages]) => {
                const input  = form.querySelector(`[name="${field}"]`);
                const holder = form.querySelector(`#${CSS.escape(field)}`);
                if (input)  input.classList.add('is-invalid');
                if (holder) {
                    holder.classList.add('invalid-feedback', 'd-block');
                    holder.textContent = messages[0];
                }
            });
            window.toastr?.error?.(json.message || 'Please fix the highlighted fields.');
            setButtonLoading(submitButton, false);
            return;
        }

        if (!res.ok || json.success === false) throw new Error(json.message || 'Request failed');

        window.toastr?.success?.(json.message || 'Promotion created.');
        setButtonLoading(submitButton, false);

        bootstrap.Modal.getOrCreateInstance(form.closest('.modal'))?.hide();
        form.reset();
        clearFormErrors(form);

        window.PromoList?.reload?.();

    } catch (err) {
        console.error('[create-promotion] failed:', err);
        window.toastr?.error?.(err.message || 'Something went wrong.');
        setButtonLoading(submitButton, false);
    }
}
</script>
