 
<div class="modal fade" id="kt_modal_add_uom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_uom">
                <h2 class="fw-bold">{{__('auth._create')}} {{__('auth._uoms')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_uom_form" class="form">
                    @csrf
                    <input type="hidden" class="form-control form-control-solid" name="umo_page" value="true"/>
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._uom')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="name" />
                                <div id="name"></div>
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._symbol')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="symbol" />
                                <div id="symbol"></div>
                            </div>
                        </div>

                        <button type="reset" class="btn btn-light me-3" id="discardButton" data-bs-dismiss="modal">{{__('auth._discard') }}</button>
                        
                        <button 
                            id="submitUOMButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitUomForm('kt_modal_add_uom_form', 'submitUOMButton', '{{ route('uom.store') }}', 'POST', 'discardButton')">
                            
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
async function submitUomForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
    const form         = document.getElementById(formId);
    const submitButton = document.getElementById(submitButtonId);
    if (!form || !submitButton) return;

    const data = Object.fromEntries(new FormData(form));
    data._method = method;

    Object.keys(data).forEach(k => {
        if (data[k] === '' || data[k] === 'undefined') delete data[k];
    });

    setButtonLoading(submitButton, true);

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
            if (json.errors) {
                Object.entries(json.errors).forEach(([field, messages]) => {
                    const input  = form.querySelector(`[name="${field}"]`);
                    const holder = form.querySelector(`#${CSS.escape(field)}`);
                    if (input)  input.classList.add('is-invalid');
                    if (holder) {
                        holder.classList.add('invalid-feedback', 'd-block');
                        holder.textContent = messages[0];
                    }
                });
            }
            window.toastr?.error?.(json.message || 'Please fix the highlighted fields.');
            setButtonLoading(submitButton, false);
            return;
        }

        if (!res.ok || json.success === false) {
            throw new Error(json.message || 'Request failed');
        }

        window.toastr?.success?.(json.message || 'UOM created.');
        setButtonLoading(submitButton, false);

        bootstrap.Modal.getOrCreateInstance(form.closest('.modal'))?.hide();
        form.reset();

        window.UOMList?.reload?.();

    } catch (err) {
        console.error('[create-uom] failed:', err);
        window.toastr?.error?.(err.message || 'Something went wrong.');
        setButtonLoading(submitButton, false);
    }
}
</script>

