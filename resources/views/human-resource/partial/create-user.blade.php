<div class="modal fade" id="kt_modal_add_employee" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_user_header">
                <h2 class="fw-bold">{{ __('auth._create') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_user_form" class="form">
                    @csrf

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth.first_name') }}</label>
                            <input type="text" name="first_name" class="form-control form-control-solid">
                            <div id="first_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth.last_name') }}</label>
                            <input type="text" name="last_name" class="form-control form-control-solid">
                            <div id="last_name"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth._email') }}</label>
                            <input type="email" name="email" class="form-control form-control-solid">
                            <div id="email"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth._phone') }}</label>
                            <input type="tel" name="telephone_number" class="form-control form-control-solid">
                            <div id="telephone_number"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth.job_title') }}</label>
                            <input type="text" name="job_title" class="form-control form-control-solid">
                            <div id="job_title"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('auth._role') }}</label>
                            <select name="role_id" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                                @endforeach
                            </select>
                            <div id="role_id"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <x-liveblade-dependent-dropdown
                            id="location_department"
                            parentName="location_id"
                            childName="department_id"
                            parentLabel="pagination._location"
                            childLabel="auth._department"
                            :parentOptions="$locations"
                            :childOptions="$departments"
                            route="{{ route('get.departments') }}"
                        />
                        <div id="location_id"></div>
                        <div id="department_id"></div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('auth._discard') }}
                        </button>
                        <button type="button" id="submitUserCreate" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label">{{ __('auth.submit') }}</span>
                            <span class="indicator-progress" style="display: none;">
                                {{ __('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
(() => {
    'use strict';

    // Local helpers — scoped to this modal, no globals, no cross-module deps
    const setBtnLoading = (btn, isLoading) => {
        if (!btn) return;
        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        if (isLoading) {
            btn.disabled = true;
            if (label)    label.style.display    = 'none';
            if (progress) progress.style.display = 'inline';
        } else {
            btn.disabled = false;
            if (label)    label.style.display    = 'inline';
            if (progress) progress.style.display = 'none';
        }
    };

    const clearErrors = (form) => {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
            el.textContent = '';
            el.classList.remove('d-block');
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('kt_modal_add_user_form');
        const btn  = document.getElementById('submitUserCreate');
        if (!form || !btn) return;

        const url = '{{ route('user.store') }}';   // ← User module owns /user
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Reset the modal state whenever it's opened
        document.getElementById('kt_modal_add_employee')?.addEventListener('show.bs.modal', () => {
            form.reset();
            clearErrors(form);
            setBtnLoading(btn, false);
        });

        btn.addEventListener('click', async () => {
            clearErrors(form);

            const data = Object.fromEntries(new FormData(form));
            data._method = 'POST';
            Object.keys(data).forEach(k => {
                if (data[k] === '' || data[k] === 'undefined') delete data[k];
            });

            setBtnLoading(btn, true);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams(data),
                });
                const json = await res.json().catch(() => ({}));

                if (res.status === 422) {
                    Object.entries(json.errors || {}).forEach(([field, msgs]) => {
                        const input  = form.querySelector(`[name="${field}"]`);
                        const holder = form.querySelector(`#${CSS.escape(field)}`);
                        if (input)  input.classList.add('is-invalid');
                        if (holder) {
                            holder.classList.add('invalid-feedback', 'd-block');
                            holder.textContent = msgs[0];
                        }
                    });
                    window.toastr?.error?.(json.message || 'Please fix the highlighted fields.');
                    setBtnLoading(btn, false);
                    return;
                }
                if (!res.ok || json.success === false) {
                    throw new Error(json.message || 'Create failed');
                }

                window.toastr?.success?.(json.message || 'User created.');
                setBtnLoading(btn, false);

                bootstrap.Modal.getInstance(form.closest('.modal'))?.hide();
                form.reset();
                clearErrors(form);

                // Refresh the User module table (NOT the Employee module)
                window.UserList?.reload?.();
            } catch (err) {
                console.error('[create-user] failed:', err);
                window.toastr?.error?.(err.message || 'Something went wrong.');
                setBtnLoading(btn, false);
            }
        });
    });
})();
</script>