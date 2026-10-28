<div class="offcanvas offcanvas-end" tabindex="-1" id="variantDrawer"
     aria-labelledby="variantDrawerLabel"
     style="width: 1000px; max-width: 100vw;">

    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-boxes fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="variantDrawerLabel">{{ __('pagination.product_variant') }}</h4>
                <span class="text-white opacity-75 fs-7" id="variantDrawerName"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body px-5 py-6" id="variantDrawerBody">
        <div id="variantDrawerSkeleton">
            <div class="var-sk var-sk-text mb-3" style="width:60%;"></div>
            <div class="var-sk var-sk-text mb-3" style="width:40%;"></div>
            <div class="var-sk var-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="variantDrawerContent" class="d-none"></div>
    </div>

    <div class="border-top p-4 d-none" id="variantDrawerFooter">
        <div class="d-flex justify-content-between align-items-center gap-3" style="min-height: 44px;">
            <button type="button" class="btn btn-light flex-shrink-0" data-bs-dismiss="offcanvas">
                <i class="bi bi-x-lg me-2"></i>{{ __('pagination.close') }}
            </button>
            <div class="d-flex align-items-center justify-content-end gap-2 flex-grow-1 flex-nowrap"
                 id="variantDrawerActions" style="min-width: 0; overflow-x: auto;"></div>
        </div>
    </div>
</div>
