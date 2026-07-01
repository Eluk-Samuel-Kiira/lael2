<section>
    <form id="updateProfileInfoForm" class="form">
        @csrf
        @method('patch')
        <div class="card-body border-top p-9">
            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth._avatar')}}</label>
                <div class="col-lg-8">
                    <div class="image-input image-input-outline" data-kt-image-input="true" style="background-image: url('assets/media/svg/avatars/blank.svg')">
                        <div class="image-input-wrapper w-125px h-125px" id="profile-img-preview" style="background-image: url(assets/media/avatars/300-1.jpg)"></div>
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="{{__('auth._change')}}">
                            <i class="ki-duotone ki-pencil fs-7">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="file" name="avatar" accept=".png, .jpg, .jpeg" onchange="previewAndUploadProfileImage(event)"/>
                            <input type="hidden" name="avatar_remove" />
                        </label>
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="{{__('auth._cancel')}}">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="{{__('auth._remove')}}">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                    </div>
                    <div class="form-text">{{__('auth.allowed_files')}}</div>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth._full_name')}}</label>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-6 fv-row">
                            <input type="text" name="first_name" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $user->first_name }}" />
                            <div id="first_name"></div>
                        </div>
                        <div class="col-lg-6 fv-row">
                            <input type="text" name="last_name" class="form-control form-control-lg form-control-solid" value="{{ $user->last_name }}" />
                            <div id="last_name"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth._email')}}</label>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12 fv-row">
                            <input type="email" name="email" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $user->email }}" />
                            <div id="email"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button id="submitProfileButton" type="submit" class="btn btn-primary">
                <span class="indicator-label">{{__('auth.submit') }}</span>
                <span class="indicator-progress" style="display: none;">
                    {{__('auth.please_wait') }}
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
    </form>
    
</section>
