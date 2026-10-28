<div class="offcanvas offcanvas-end" tabindex="-1" id="serialVariantDrawer"
     aria-labelledby="serialVariantDrawerLabel"
     style="width: 1000px; max-width: 100vw;">

    <div class="offcanvas-header bg-dark py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="ki-duotone ki-upc-scan fs-2 text-white">
                <span class="path1"></span><span class="path2"></span>
            </i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="serialVariantDrawerLabel">
                    {{ __('passwords.serial_numbers') }}
                </h4>
                <span class="text-white opacity-75 fs-7" id="serialVariantDrawerName"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body px-5 py-6" id="serialVariantDrawerBody">
        <div id="serialVariantDrawerSkeleton">
            <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                <div class="flex-grow-1">
                    <div class="bg-light-primary rounded" style="height:14px;width:60%;margin-bottom:8px;"></div>
                    <div class="bg-light-primary rounded" style="height:10px;width:40%;"></div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                <div class="flex-grow-1">
                    <div class="bg-light-primary rounded" style="height:14px;width:55%;margin-bottom:8px;"></div>
                    <div class="bg-light-primary rounded" style="height:10px;width:35%;"></div>
                </div>
            </div>
        </div>
        <div id="serialVariantDrawerContent" class="d-none"></div>
    </div>

    <div class="border-top p-4 d-none" id="serialVariantDrawerFooter">
        <div class="d-flex justify-content-between align-items-center gap-3" style="min-height: 44px;">
            <button type="button" class="btn btn-light flex-shrink-0" data-bs-dismiss="offcanvas">
                <i class="bi bi-x-lg me-2"></i>{{ __('pagination.close') }}
            </button>
            <div class="d-flex align-items-center justify-content-end gap-2 flex-grow-1 flex-nowrap"
                 id="serialVariantDrawerActions" style="min-width: 0; overflow-x: auto;"></div>
        </div>
    </div>
</div>