<div class="modal fade" id="userEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth.edit_user') }} — <span id="userEditHeaderName">—</span></h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="userEditForm" class="form">
                    @csrf
                    <input type="hidden" id="userEditId">

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
                            </select>
                            <div id="role_id"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('pagination._location') }}</label>
                            <select name="location_id" id="userEditLocation" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                            </select>
                            <div id="location_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth._department') }}</label>
                            <select name="department_id" id="userEditDepartment" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                            </select>
                            <div id="department_id"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button type="button" id="userEditSubmit" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label"><i class="bi bi-check2 me-1"></i>{{ __('auth._update') }}</span>
                            <span class="indicator-progress" style="display: none;">{{ __('auth.please_wait') }}<span class="spinner-border spinner-border-sm ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>