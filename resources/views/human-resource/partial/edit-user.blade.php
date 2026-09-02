
<div class="modal fade delete-user-modal" id="editUserModal{{$employee->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth.edit_employee') }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body scroll-y mx-lg-5 my-7">
                <div id="status"></div>
                <form id="edit_user_form{{ $employee->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth.first_name')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="first_name" value="{{ $employee->first_name }}" />
                                <div id="first_name{{ $employee->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth.last_name')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="last_name" value="{{ $employee->last_name }}" />
                                <div id="last_name{{ $employee->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                        <span class="required">{{__('auth._email')}}</span>
                                </label>
                                <input type="email" class="form-control form-control-solid" name="email" value="{{ $employee->email }}" />
                                <div id="email{{ $employee->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                        <span class="required">{{__('auth._phone')}}</span>
                                </label>
                                <input type="tel" class="form-control form-control-solid" name="telephone_number" value="{{ $employee->telephone_number }}" />
                                <div id="telephone_number{{ $employee->id }}"></div>
                            </div>
                        </div>
                        
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                        <span class="required">{{__('auth.job_title')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" value="{{ $employee->job_title }}" name="job_title" />
                                <div id="job_title{{ $employee->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('auth._role') }}</span>
                                </label>
                                <select name="role_id" class="form-select">
                                    <option value="" disabled {{ is_null($employee->role) ? 'selected' : '' }}></option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" {{ $role->id == $employee->role_id ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="role_id{{ $employee->id }}"></div>
                            </div>

                        </div>

                        <div class="row g-9 mb-8">

                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('auth._department') }}</span>
                                </label>
                                <x-liveblade-dependent-dropdown 
                                    id="location_department_{{ $employee->id }}"
                                    parentName="location_id"
                                    childName="department_id"
                                    parentLabel="pagination._location"
                                    childLabel="auth._department"
                                    :parentOptions="$locations"
                                    :childOptions="$departments"
                                    route="{{ route('get.departments') }}"
                                    selectedParent="{{ $employee->location_id }}"
                                    selectedChild="{{ $employee->department_id }}"
                                    skipAjax="true"
                                />
                                <div id="department_id{{ $employee->id }}"></div>
                                <div id="locations_id{{ $employee->id }}"></div>
                        </div>
                    </div>
                        
                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $employee->id }}" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button type="button" onclick="editEmployeeInstanceLoop({{$employee->id }})" class="btn btn-primary" id="submitEmplButton{{ $employee->id }}">
                            <span class="indicator-label">{{ __('auth._update') }}</span>
                            <span class="indicator-progress" style="display: none;">
                                {{__('auth.please_wait')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>





