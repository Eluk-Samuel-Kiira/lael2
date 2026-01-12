
<div class="modal fade delete-user-modal" id="editUserDeptModal{{$employee->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth.user_department') }} {{ $employee->first_name }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            @unless(tenant_is_single_shop(auth()->user()->tenant_id))
                <div class="modal-body scroll-y mx-lg-5 my-7">
                    <form id="edit_user_form{{ $employee->id }}" 
                        class="form"
                        action="{{ route('employees.updateDepartments', $employee->id) }}" 
                        method="POST">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="user_id" value="{{ $employee->id }}">

                        <!-- DEPARTMENT ASSIGNMENT -->
                        <div class="mb-5">
                            <h6 class="fw-bold mb-3">{{ __('auth._department') }}</h6>
                            <div class="row g-3">
                                @foreach ($departments as $department)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                class="form-check-input"
                                                name="departments[]" 
                                                value="{{ $department->id }}"
                                                id="dept-{{ $employee->id }}-{{ $department->id }}"
                                                {{ optional($employee->departments)->contains($department->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="dept-{{ $employee->id }}-{{ $department->id }}">
                                                {{ ucwords(str_replace('_', ' ', $department->name)) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Optional error/feedback container -->
                            <div id="departments{{ $employee->id }}"></div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="d-flex justify-content-center pt-10">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">
                                {{ __('auth._discard') }}
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">{{ __('auth._update') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>{{ __('auth.single_shop_plan') }}:</strong>
                    {{ __('auth.upgrade_for_multiple_shops') }}
                    <a href="/" class="btn btn-sm btn-outline-primary ms-2">
                        {{ __('auth.upgrade_plan') }}
                    </a>
                </div>
            @endunless
            
        </div>
    </div>
</div>
        





