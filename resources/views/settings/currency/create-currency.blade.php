 
<div class="modal fade" id="kt_modal_add_currency" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth.new_currency')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_currency_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._code')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="code" />
                                <div id="code"></div>
                            </div>
                        </div>
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._name')}}</span>
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
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._exchange_rate')}}{{ tenant_currency(auth()->user()->tenant_id) }}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="exchange_rate" />
                                <div id="exchange_rate"></div>
                            </div>
                        </div>

                        <button type="reset" class="btn btn-light me-3" id="discardButton" data-bs-dismiss="modal">Discard</button>
                        <button 
                            id="submitCurrencyButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitCurrencyForm('kt_modal_add_currency_form', 'submitCurrencyButton', '{{ route('currency.store') }}', 'POST', 'discardButton')">

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
async function submitCurrencyForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
    const form         = document.getElementById(formId);
    const submitButton = document.getElementById(submitButtonId);
    if (!form || !submitButton) return;

    const data = Object.fromEntries(new FormData(form));
    data._method = method;

    data.is_base_currency = form.querySelector('[name="is_base_currency"]')?.checked ? 1 : 0;
    data.is_active        = form.querySelector('[name="is_active"]')?.checked        ? 1 : 0;

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

        window.toastr?.success?.(json.message || 'Currency created.');
        setButtonLoading(submitButton, false);

        bootstrap.Modal.getOrCreateInstance(form.closest('.modal'))?.hide();
        form.reset();
        clearFormErrors(form);

        window.CurrencyList?.reload?.();

    } catch (err) {
        console.error('[create-currency] failed:', err);
        window.toastr?.error?.(err.message || 'Something went wrong.');
        setButtonLoading(submitButton, false);
    }
}
</script>