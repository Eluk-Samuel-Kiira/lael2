 
<div class="modal fade" id="editDepartment{{ $department->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_department">
                <h2 class="fw-bold">{{__('auth._edit_department')}} - {{ $department->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_department_form{{ $department->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._name')}}</span>
                                </label>
                                <input type="text" value="{{ $department->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $department->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <!-- Manager -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
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
                                    selected="{{ $department->manager_id }}"
                                    placeholder="Type or select manager..."
                                />
                                <div id="manager_id{{ $department->id }}"></div>
                            </div>

                            <!-- Location -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth.location')}}</span>
                                </label>
                                @php
                                    $formattedLocations = [];
                                    foreach($locations as $location) {
                                        $formattedLocations[] = (object)[
                                            'id' => $location->id,
                                            'name' => $location->name
                                        ];
                                    }
                                @endphp
                                <x-typable-select 
                                    name="location_id"
                                    :options="$formattedLocations"
                                    selected="{{ $department->location_id }}"
                                    placeholder="Type or select location..."
                                />
                                <div id="location_id{{ $department->id }}"></div>
                            </div>
                        </div>

                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $department->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                        <button onclick="editInstanceLoopDept({{$department->id }})" id="editDepartmentButton{{ $department->id }}" type="button" class="btn btn-primary" id>
                            <span class="indicator-label">{{__('auth._update')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>  





