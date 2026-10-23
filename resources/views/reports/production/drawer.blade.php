<div class="offcanvas offcanvas-end" tabindex="-1" id="reportDrawer"
     aria-labelledby="reportDrawerLabel" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="ki-duotone ki-document fs-2 text-white">
                <span class="path1"></span><span class="path2"></span>
            </i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="reportDrawerLabel">
                    {{ __('pagination.production_order_details') }}
                </h4>
                <span class="text-white opacity-75 fs-7" id="reportDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body px-5 py-6">
        <div id="reportDrawerSkeleton">
            <div class="skeleton skeleton-text mb-3" style="width:60%;"></div>
            <div class="skeleton skeleton-text mb-3" style="width:40%;"></div>
            <div class="skeleton skeleton-text mb-3" style="width:80%;"></div>
        </div>
        <div id="reportDrawerContent" class="d-none"></div>
    </div>
</div>