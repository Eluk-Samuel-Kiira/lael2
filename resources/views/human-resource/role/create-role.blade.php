<div class="modal fade" id="kt_modal_add_role" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('auth.new_role')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7">
                <div id="status"></div>
                <form id="kt_modal_add_role_form" class="form">
                    @csrf
                    <div class="fv-row mb-10">
                        <label class="fs-5 fw-bold form-label mb-2">
                            <span class="required">{{__('auth._role')}}</span>
                        </label>
                        <input class="form-control form-control-solid" type="text" placeholder="Enter a role name" name="name" required/>
                        <div id="name"></div>
                    </div>
                    <div class="fv-row">
                        <label class="fs-5 fw-bold form-label mb-2">{{__('auth.role_permission')}}</label>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <tbody class="text-gray-600 fw-semibold">
                                    <tr>
                                        <td class="text-gray-800">
                                            <label class="form-check form-check-custom form-check-solid me-9">
                                                <input class="form-check-input" type="checkbox" id="kt_roles_select_all" />
                                                <span class="form-check-label" for="kt_roles_select_all">{{__('auth.select_all')}}</span>
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
                                                                value="{{ $permission->id }}" id="permission{{ $permission->id }}" name="permissions[]" />
                                                            <span class="form-check-label">{{ $permission->name }}</span>
                                                        </label><br>
                                                    </div>
                                                @endforeach
                                                <div id="permissions"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="text-center pt-15">
                        <button id="discardButton" type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">{{__('auth._discard') }}</button>
                        
                        <button 
                            id="submitRoleButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitRoleForm('kt_modal_add_role_form', 'submitRoleButton', '{{ route('role.store') }}', 'POST', 'discardButton')">
                            
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
    
    // Master checkbox logic
    document.getElementById('kt_roles_select_all').addEventListener('change', function () {
        const isChecked = this.checked;
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
        });
    });
</script>






