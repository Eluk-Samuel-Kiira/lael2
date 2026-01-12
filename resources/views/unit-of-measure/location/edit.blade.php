 
<div class="modal fade" id="editLocation{{ $location->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_department">
                <h2 class="fw-bold">{{__('pagination.locations_edit')}} - {{ $location->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_location_form{{ $location->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._name')}}</span>
                                </label>
                                <input type="text" value="{{ $location->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $location->id }}"></div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._manager')}}</span>
                                </label>
                                <select name="manager_id" class="form-select">
                                    <option value="">{{ __('auth._select') }}</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" {{ $location->manager_id == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="manager_id{{ $location->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._address')}}</span>
                                </label>
                                <input type="text" value="{{ $location->address }}" class="form-control form-control-solid" name="address" />
                                <div id="address{{ $location->id }}"></div>
                            </div>
                        </div>

                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $location->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                        <button onclick="editInstanceLoopLocation({{$location->id }})" id="editLocationButton{{ $location->id }}" type="button" class="btn btn-primary">
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





