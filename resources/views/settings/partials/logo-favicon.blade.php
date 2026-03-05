<div class="card-body border-top p-4 p-sm-6">
    <div class="row">
        <!-- Logo Section -->
        <div class="col-12 col-sm-6 mb-5 mb-sm-0">
            <div class="d-flex flex-column align-items-center align-items-sm-start border-bottom border-sm-0 border-end-sm-1 border-gray-300 pb-5 pb-sm-0 pe-sm-6">
                <div class="w-100 text-center text-sm-start mb-3">
                    <label class="fw-semibold fs-6">{{__('auth._logo') }}</label>
                </div>
                
                <div class="d-flex justify-content-center justify-content-sm-start w-100">
                    <div class="image-input image-input-outline" data-kt-image-input="true" style="background-image: url('assets/media/svg/avatars/blank.svg')">
                        <div class="image-input-wrapper w-100px w-sm-125px h-100px h-sm-125px" data-preview="logo-preview" 
                             style="background-image: url(assets/media/logos/default-dark.svg); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                        
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                               data-kt-image-input-action="change" data-bs-toggle="tooltip" title="{{__('auth._change') }}">
                            <i class="ki-duotone ki-pencil fs-7">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <input type="file" data-type="logo_image" accept=".png, .jpg, .jpeg" onchange="previewAndUploadLogoOrFavicon(event)" />
                        </label>
                        
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                              data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="{{__('auth._cancel') }}">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                              data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="{{__('auth._remove') }}">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                    </div>
                </div>
                
                <div class="text-muted small text-center text-sm-start mt-2">
                    {{ __('auth.recommended_size_logo') }}
                </div>
            </div>
        </div>

        <!-- Favicon Section -->
        <div class="col-12 col-sm-6">
            <div class="d-flex flex-column align-items-center align-items-sm-start ps-sm-6">
                <div class="w-100 text-center text-sm-start mb-3">
                    <label class="fw-semibold fs-6">{{__('auth._favicon') }}</label>
                </div>
                
                <div class="d-flex justify-content-center justify-content-sm-start w-100">
                    <div class="image-input image-input-outline" data-kt-image-input="true" style="background-image: url('assets/media/svg/avatars/blank.svg')">
                        <div class="image-input-wrapper w-75px w-sm-100px h-75px h-sm-100px" data-preview="favicon-preview" 
                             style="background-image: url(assets/media/logos/favicon.png); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                        
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                               data-kt-image-input-action="change" data-bs-toggle="tooltip" title="{{__('auth._change') }}">
                            <i class="ki-duotone ki-pencil fs-7">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <input type="file" data-type="favicon_image" accept=".png, .jpg, .jpeg" onchange="previewAndUploadLogoOrFavicon(event)" />
                        </label>
                        
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                              data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="{{__('auth._cancel') }}">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" 
                              data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="{{__('auth._remove') }}">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                    </div>
                </div>
                
                <div class="text-muted small text-center text-sm-start mt-2">
                    {{ __('auth.recommended_size_favicon') }}
                </div>
            </div>
        </div>
    </div>
</div>