{{-- ═══════════════════════════════════════════════════════════════
     CATEGORY DRAWER — offcanvas on the right.
     Single instance per page. Body filled by CategoryDrawer.open(id).
     ═══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="categoryDrawer" aria-labelledby="categoryDrawerLabel"
     style="width: 620px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-tag fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="categoryDrawerLabel">
                    {{ __('pagination._category') }}
                </h4>
                <span class="text-white opacity-75 fs-7" id="categoryDrawerName"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body px-5 py-6" id="categoryDrawerBody">
        <div id="categoryDrawerSkeleton">
            <div class="cat-sk cat-sk-text mb-3" style="width:60%;"></div>
            <div class="cat-sk cat-sk-text mb-3" style="width:40%;"></div>
            <div class="cat-sk cat-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="categoryDrawerContent" class="d-none"></div>
    </div>

    <div class="border-top p-4 d-none" id="categoryDrawerFooter">
        <div class="d-flex justify-content-between align-items-center gap-3" style="min-height: 44px;">
            <button type="button" class="btn btn-light flex-shrink-0" data-bs-dismiss="offcanvas">
                <i class="bi bi-x-lg me-2"></i>{{ __('payments.close') ?? 'Close' }}
            </button>
            <div class="d-flex align-items-center justify-content-end gap-2 flex-grow-1 flex-nowrap"
                 id="categoryDrawerActions"
                 style="min-width: 0; overflow-x: auto;"></div>
        </div>
    </div>
</div>