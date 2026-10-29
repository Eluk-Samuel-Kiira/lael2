<div class="modal fade" id="kt_modal_add_customer_group" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth._create') }} {{ __('passwords.customer_group') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_customer_group_form" class="form">
                    @csrf
                    <div class="text-center pt-10">

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('auth._name') }}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="name"
                                       placeholder="e.g., Wholesale" />
                                <div id="name"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.discount_percentage') }}</span>
                                </label>
                                <div class="input-group input-group-solid">
                                    <input type="number" class="form-control form-control-solid"
                                           name="discount_percentage"
                                           placeholder="0.00" step="0.01" min="0" max="100" value="0" />
                                    <span class="input-group-text">%</span>
                                </div>
                                <div id="discount_percentage"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.default') }}</span>
                                </label>
                                <div class="form-check form-switch form-check-custom form-check-solid mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default">
                                    <label class="form-check-label" for="is_default">
                                        {{ __('passwords.mark_as_default_group') }}
                                    </label>
                                </div>
                                <div id="is_default"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="col-md-12 text-end">
                                <button type="reset" class="btn btn-light me-3" id="discardCustomerGroupButton" data-bs-dismiss="modal">
                                    {{ __('auth._discard') }}
                                </button>

                                <button id="submitCustomerGroupButton"
                                        type="button"
                                        class="btn btn-primary"
                                        onclick="submitCustomerGroupForm('kt_modal_add_customer_group_form', 'submitCustomerGroupButton', '{{ route('customer-group.store') }}', 'POST', 'discardCustomerGroupButton')">
                                    <span class="indicator-label">{{ __('auth.submit') }}</span>
                                    <span class="indicator-progress">{{ __('auth.please_wait') }}
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function submitCustomerGroupForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
    const form         = document.getElementById(formId);
    const submitButton = document.getElementById(submitButtonId);
    if (!form || !submitButton) return;

    const data = Object.fromEntries(new FormData(form));
    data._method = method;

    data.is_default = form.querySelector('[name="is_default"]')?.checked ? 1 : 0;

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

        window.toastr?.success?.(json.message || 'Group created.');
        setButtonLoading(submitButton, false);

        bootstrap.Modal.getOrCreateInstance(form.closest('.modal'))?.hide();
        form.reset();
        clearFormErrors(form);

        window.CGList?.reload?.();

    } catch (err) {
        console.error('[create-customer-group] failed:', err);
        window.toastr?.error?.(err.message || 'Something went wrong.');
        setButtonLoading(submitButton, false);
    }
}
</script>