<div class="card-body border-top p-9 d-flex">

    <div class="d-flex flex-column align-items-center pe-6" style="flex: 1; border-right: 1px solid #e0e0e0;">
        <div class="row mb-6">
            <label class="col-form-label fw-semibold fs-6">{{__('auth._logo') }}</label>
            <div class="col">
                <div class="image-input image-input-outline" data-kt-image-input="true" style="background-image: url('assets/media/svg/avatars/blank.svg')">
                    <div class="image-input-wrapper w-125px h-125px" data-preview="logo-preview" style="background-image: url(assets/media/logos/default-dark.svg)"></div>
                    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="{{__('auth._change') }}">
                        <i class="ki-duotone ki-pencil fs-7">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="file" data-type="logo_image" accept=".png, .jpg, .jpeg" onchange="previewAndUploadLogoOrFavicon(event)" />
                    </label>
                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="{{__('auth._cancel') }}">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>
                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="{{__('auth._remove') }}">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column align-items-center ps-6" style="flex: 1;">
        <div class="row mb-6">
            <label class="col-form-label fw-semibold fs-6">{{__('auth._favicon') }}</label>
            <div class="col">
                <div class="image-input image-input-outline" data-kt-image-input="true" style="background-image: url('assets/media/svg/avatars/blank.svg')">
                    <div class="image-input-wrapper w-125px h-125px" data-preview="favicon-preview" style="background-image: url(assets/media/logos/favicon.png)"></div>
                    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="{{__('auth._change') }}">
                        <i class="ki-duotone ki-pencil fs-7">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="file" data-type="favicon_image" accept=".png, .jpg, .jpeg" onchange="previewAndUploadLogoOrFavicon(event)" />
                    </label>
                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="{{__('auth._cancel') }}">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>
                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="{{__('auth._remove') }}">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
