<div class="modal fade" id="kt_modal_add_tax" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_department">
                <h2 class="fw-bold">{{__('auth._create')}} {{__('pagination._tax')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_tax_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._tax')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="name" />
                                <div id="name"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._code')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="code" maxlength="6"/>
                                <div id="code"></div>
                            </div>
                        </div>
                        
                        <div class="row g-9 mb-8">
                            <div class="mb-10 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination._type')}}</label>
                                <select name="type" class="form-select">
                                    <option></option>
                                    <option value="percentage">{{__('pagination._percentage')}}</option>
                                    <option value="fixed">{{__('pagination._fixed')}}</option>
                                </select>
                                <div id="type"></div>
                            </div>
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._rate')}}</span>
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="rate"/>
                                <div id="rate"></div>
                            </div>
                        </div>

                        <!-- Withholding Tax Checkbox -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_withholding_tax" id="is_withholding_tax" value="1">
                                    <label class="form-check-label fw-semibold" for="is_withholding_tax">
                                        <i class="bi bi-receipt me-1"></i>
                                        {{ __('passwords.withholding_tax') }}
                                    </label>
                                </div>
                                <div class="text-muted fs-7 mt-1">{{ __('passwords.withholding_tax_help') }}</div>
                            </div>
                        </div>

                        <button type="reset" class="btn btn-light me-3" id="discardTaxButton" data-bs-dismiss="modal">{{__('auth._discard') }}</button>
                        
                        <button 
                            id="submitTaxButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitTaxForm('kt_modal_add_tax_form', 'submitTaxButton', '{{ route('tax.store') }}', 'POST', 'discardTaxButton')">
                            
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
async function submitTaxForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
    const form         = document.getElementById(formId);
    const submitButton = document.getElementById(submitButtonId);
    if (!form || !submitButton) return;

    const data = Object.fromEntries(new FormData(form));
    data._method = method;

    // Checkbox: always send 0 or 1
    data.is_withholding_tax = form.querySelector('[name="is_withholding_tax"]')?.checked ? 1 : 0;

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

        if (!res.ok || json.success === false) {
            throw new Error(json.message || 'Request failed');
        }

        window.toastr?.success?.(json.message || 'Tax created.');
        setButtonLoading(submitButton, false);

        bootstrap.Modal.getOrCreateInstance(form.closest('.modal'))?.hide();
        form.reset();
        clearFormErrors(form);

        window.TaxList?.reload?.();

    } catch (err) {
        console.error('[create-tax] failed:', err);
        window.toastr?.error?.(err.message || 'Something went wrong.');
        setButtonLoading(submitButton, false);
    }
}
</script>