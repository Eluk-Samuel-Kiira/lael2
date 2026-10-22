<div class="offcanvas offcanvas-end" tabindex="-1" id="poDrawer"
     aria-labelledby="poDrawerLabel" style="width: 780px; max-width: 100vw; overflow: visible;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="ki-duotone ki-document fs-2 text-white">
                <span class="path1"></span><span class="path2"></span>
            </i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="poDrawerLabel">
                    {{ __('passwords.purchase_order') }}
                </h4>
                <span class="text-white opacity-75 fs-7" id="poDrawerNumber"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body px-5 py-6" id="poDrawerBody">
        <div id="poDrawerSkeleton">
            <div class="skeleton skeleton-text mb-3" style="width:60%;"></div>
            <div class="skeleton skeleton-text mb-3" style="width:40%;"></div>
            <div class="skeleton skeleton-text mb-3" style="width:80%;"></div>
        </div>
        <div id="poDrawerContent" class="d-none"></div>
    </div>

    <div class="border-top p-4 d-none" id="poDrawerFooter">
        <div class="d-flex justify-content-between align-items-center gap-3" style="min-height: 44px;">
            <button type="button" class="btn btn-light flex-shrink-0" data-bs-dismiss="offcanvas">
                <i class="bi bi-x-lg me-2"></i>{{ __('payments.close') ?? 'Close' }}
            </button>
            <div class="d-flex align-items-center justify-content-end gap-2 flex-grow-1" id="poDrawerActions" style="min-width: 0; flex-wrap: wrap; row-gap: .5rem;"></div>
        </div>
    </div>
</div>


