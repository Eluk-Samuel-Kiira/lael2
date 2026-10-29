    
    <div class="modal fade" id="kt_modal_add_location" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-850px">
            <div class="modal-content">
                <div class="modal-header" id="kt_modal_add_currency">
                    <h2 class="fw-bold">{{__('pagination.locations_new')}}</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body px-5 my-7">
                    <form id="kt_modal_add_location_form" class="form">
                        @csrf
                        <div class="text-center pt-10">
                            <div class="row g-9 mb-8">
                                <div class="d-flex flex-column mb-8 fv-row col-md-5">
                                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                        <span class="required">{{__('auth._name')}}</span>
                                    </label>
                                    <input type="text" class="form-control form-control-solid" name="name" />
                                    <div id="name"></div>
                                </div>

                                <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                        <span class="required">{{__('auth._manager')}}</span>
                                    </label>
                                    @php
                                        $formattedUsers = [];
                                        foreach($users as $user) {
                                            $formattedUsers[] = (object)[
                                                'id' => $user->id,
                                                'name' => $user->name
                                            ];
                                        }
                                    @endphp
                                    <x-typable-select 
                                        name="manager_id"
                                        :options="$formattedUsers"
                                        placeholder="Type or select manager..."
                                    />
                                    <div id="manager_id"></div>
                                </div>

                                <div class="d-flex flex-column mb-8 fv-row col-md-3">
                                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                        <span class="required">{{__('auth._currency')}}</span>
                                    </label>
                                    @php
                                        $formattedCurrencies = [];
                                        foreach($currencies as $currency) {
                                            $formattedCurrencies[] = (object)[
                                                'id' => $currency->id,
                                                'name' => $currency->name . ' ' . $currency->code
                                            ];
                                        }
                                    @endphp
                                    <x-typable-select 
                                        name="currency_id"
                                        :options="$formattedCurrencies"
                                        placeholder="Type or select currency..."
                                    />
                                    <div id="currency_id"></div>
                                </div>
                            </div>

                            <div class="row g-9 mb-8">
                                <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                        <span class="required">{{__('pagination._address')}}</span>
                                    </label>
                                    <input type="text" class="form-control form-control-solid" name="address" />
                                    <div id="address"></div>
                                </div>
                            </div>

                            <button type="reset" class="btn btn-light me-3" id="discardButton" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                            <button 
                                id="submitLocationButton" 
                                type="button" 
                                class="btn btn-primary"
                                onclick="submitLocationForm('kt_modal_add_location_form', 'submitLocationButton', '{{ route('locations.store') }}', 'POST', 'discardButton')">
                                
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
async function submitLocationForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
    const form         = document.getElementById(formId);
    const submitButton = document.getElementById(submitButtonId);
    if (!form || !submitButton) return;

    // ── Collect form data ─────────────────────────────────
    const data = Object.fromEntries(new FormData(form));
    data._method = method;

    // Drop empty values so nullable rules apply
    Object.keys(data).forEach(k => {
        if (data[k] === '' || data[k] === 'undefined') delete data[k];
    });

    // ── Loading state ────────────────────────────────────
    setButtonLoading(submitButton, true);
    clearLocationFormErrors(form);

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

        // ── Validation errors (422) ──────────────────────
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

        // ── Success ──────────────────────────────────────
        window.toastr?.success?.(json.message || 'Location created.');
        setButtonLoading(submitButton, false);

        bootstrap.Modal.getOrCreateInstance(form.closest('.modal'))?.hide();
        form.reset();
        clearLocationFormErrors(form);

        // Refresh the list
        window.LocationList?.reload?.();

    } catch (err) {
        console.error('[create-location] failed:', err);
        window.toastr?.error?.(err.message || 'Something went wrong.');
        setButtonLoading(submitButton, false);
    }
}

// Clear all field-level errors on the form
function clearLocationFormErrors(form) {
    if (!form) return;
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
        el.textContent = '';
        el.classList.remove('d-block');
    });
}
</script>

