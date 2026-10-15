{{-- resources/views/manufacturing/production-order/view-modal-shared.blade.php --}}

<div class="modal fade"
     id="viewProductionModalShared"
     tabindex="-1"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-order-id="">

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"
         style="max-width: 1100px;">

        <div class="modal-content">

            <div class="modal-header">
                <h2 class="fw-bold d-flex align-items-center">
                    <i class="ki-duotone ki-eye fs-2 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span class="po-view-title">
                        {{ __('passwords.production_order_details') }}
                    </span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <div class="po-view-content">
                    <div class="text-center py-10">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('pagination.close') }}
                </button>
            </div>

        </div>
    </div>
</div>