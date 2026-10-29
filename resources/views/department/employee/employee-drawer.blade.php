<div class="offcanvas offcanvas-end" tabindex="-1" id="employeeDrawer"
     aria-labelledby="employeeDrawerLabel"
     style="width: 1000px; max-width: 100vw;">

    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="ki-duotone ki-profile-circle fs-2 text-white"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="employeeDrawerLabel">{{ __('pagination.employee') }}</h4>
                <span class="text-white opacity-75 fs-7" id="employeeDrawerName"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body px-5 py-6" id="employeeDrawerBody">
        <div id="employeeDrawerSkeleton">
            <div class="bg-light-primary rounded mb-2" style="height:14px;width:60%;"></div>
            <div class="bg-light-primary rounded" style="height:10px;width:40%;"></div>
        </div>
        <div id="employeeDrawerContent" class="d-none"></div>
    </div>

    <div class="border-top p-4 d-none" id="employeeDrawerFooter">
        <div class="d-flex justify-content-between align-items-center gap-3" style="min-height: 44px;">
            <button type="button" class="btn btn-light flex-shrink-0" data-bs-dismiss="offcanvas">
                <i class="bi bi-x-lg me-2"></i>{{ __('auth._discard') }}
            </button>
            <div class="d-flex align-items-center justify-content-end gap-2 flex-grow-1 flex-nowrap"
                 id="employeeDrawerActions" style="min-width: 0; overflow-x: auto;"></div>
        </div>
    </div>
</div>