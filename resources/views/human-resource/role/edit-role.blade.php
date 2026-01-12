<div class="modal fade edit-role-modal" id="edit_role{{ $role->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth.edit_role') }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7">
                <div id="status"></div>
                <form id="edit_role_form{{ $role->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="fv-row mb-10">
                        <label class="fs-5 fw-bold form-label mb-2">
                            <span class="required">{{ __('auth._role') }}</span>
                        </label>
                        <input class="form-control form-control-solid" type="text" value="{{ $role->name }}" name="name" required/>
                        <div id="name{{ $role->id }}"></div>
                    </div>
                    <div class="fv-row">
                        <label class="fs-5 fw-bold form-label mb-2">{{ __('auth.role_permission') }}</label>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <tbody class="text-gray-600 fw-semibold">
                                    <tr>
                                        <td class="text-gray-800">
                                            <label class="form-check form-check-custom form-check-solid me-9">
                                                <input class="form-check-input" type="checkbox" id="kt_roles_select_all{{ $role->id }}" />
                                                <span class="form-check-label">{{ __('auth.select_all') }}</span>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="row">
                                                @foreach ($permissions as $permission)
                                                    <div class="col-md-4">
                                                        <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                            <input class="form-check-input permission-checkbox" type="checkbox" 
                                                                value="{{ $permission->id }}" 
                                                                id="permission{{ $permission->id }}" 
                                                                name="permissions[]"
                                                                {{ in_array($permission->id, $role->permissions->pluck('id')->toArray()) ? 'checked' : '' }} />
                                                            <span class="form-check-label">{{ $permission->name }}</span>
                                                        </label><br>
                                                    </div>
                                                @endforeach
                                                <div id="permissions{{ $role->id }}"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $role->id }}" data-bs-dismiss="modal">{{__('auth._discard') }}</button>
                        <button type="button" onclick="editInstanceLoopRole({{ $role->id }})" class="btn btn-primary" id="submitButton{{ $role->id }}">
                            <span class="indicator-label">{{__('auth.submit') }}</span>
                            <span class="indicator-progress" style="display: none;">
                                {{__('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



