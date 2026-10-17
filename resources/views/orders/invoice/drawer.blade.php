{{-- ═══════════════════════════════════════════════════════════
     INVOICE DRAWER — Bootstrap offcanvas on the right.
     Single instance for the whole page; content is filled by
     window.InvoiceDrawer.open(id) from /invoices/{id}/details.
     ═══════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="invoiceDrawer" aria-labelledby="invoiceDrawerLabel"
     style="width: 780px; max-width: 100vw; overflow: visible;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-file-earmark-text fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="invoiceDrawerLabel">
                    {{ __('payments.invoice_details') ?? 'Invoice details' }}
                </h4>
                <span class="text-white opacity-75 fs-7" id="invoiceDrawerNumber"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body px-5 py-6" id="invoiceDrawerBody">
        <div id="invoiceDrawerSkeleton">
            <div class="skeleton skeleton-text mb-3" style="width:60%;"></div>
            <div class="skeleton skeleton-text mb-3" style="width:40%;"></div>
            <div class="skeleton skeleton-text mb-3" style="width:80%;"></div>
            <div class="skeleton skeleton-text mb-3" style="width:50%;"></div>
            <div class="skeleton skeleton-text mb-3" style="width:70%;"></div>
        </div>

        <div id="invoiceDrawerContent" class="d-none"></div>
    </div>

    <div class="border-top p-4 d-none" id="invoiceDrawerFooter">
        <div class="d-flex justify-content-between align-items-center gap-3" style="min-height: 44px;">
            <button type="button" class="btn btn-light flex-shrink-0" data-bs-dismiss="offcanvas">
                <i class="bi bi-x-lg me-2"></i>{{ __('payments.close') ?? 'Close' }}
            </button>
            <div class="d-flex align-items-center justify-content-end gap-2 flex-grow-1 flex-nowrap"
                id="invoiceDrawerActions"
                style="min-width: 0; overflow-x: auto;">
            </div>
        </div>
    </div>
</div>
<style>
    .btn-group.dropup > .dropdown-menu {
    top: auto;
    bottom: 100%;
    margin-top: 0;
    margin-bottom: 0.125rem;
}
</style>