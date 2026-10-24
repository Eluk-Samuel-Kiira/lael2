<div class="modal fade" id="kt_modal_edit_pcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('pagination.product_category_edit')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_pcategory_form" class="form" onsubmit="return false;">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="category_id" id="edit_pcat_id" value="">
                    
                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2"><span class="required">{{__('pagination._category')}}</span></label>
                            <input type="text" class="form-control form-control-solid" name="name" id="edit_pcat_name" />
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="mb-10 fv-row col-md-6">
                            <label class="required form-label">{{__('pagination.parent_category')}}</label>
                            <select name="parent_category_id" id="edit_pcat_parent" class="form-select"></select>
                        </div>
                        <div class="mb-10 fv-row col-md-6">
                            <label class="required form-label">{{__('auth._status')}}</label>
                            <select name="is_active" id="edit_pcat_status" class="form-select">
                                <option value="1">{{__('auth._active')}}</option>
                                <option value="0">{{__('auth._inactive')}}</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2"><span class="required">{{__('pagination._description')}}</span></label>
                            <textarea class="form-control form-control-solid" name="description" id="edit_pcat_description"></textarea>
                        </div>
                    </div>

                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                    <button type="button" onclick="submitEditProductCategoryForm()" id="submitEditPcatButton" class="btn btn-primary">
                        <span class="indicator-label">{{__('auth._update')}}</span>
                        <span class="indicator-progress">{{__('auth.please_wait') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    async function submitEditProductCategoryForm() {
        const submitButton = document.getElementById('submitEditPcatButton');
        const form = document.getElementById('kt_modal_edit_pcategory_form');
        if (!form) return;

        const uniqueId = document.getElementById('edit_pcat_id').value;
        if (!uniqueId) return;

        const data = Object.fromEntries(new FormData(form));
        const url  = window.PCATEGORY_ROUTES.update.replace('__ID__', uniqueId);

        setModalLoading(submitButton, true);
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({ ...data, _method: 'PUT' }),
            });
            const json = await res.json();
            if (!res.ok || json.success === false) throw new Error(json.message || 'Failed');

            window.toastr?.success?.(json.message);
            bootstrap.Modal.getOrCreateInstance(form.closest('.modal')).hide();

            // Refresh the list
            window.ProductCategoryList?.reload?.();

            // Refresh the drawer if it was open for this category
            if (window.ProductCategoryDrawer?.isOpenFor?.(uniqueId)) {
                window.ProductCategoryDrawer.reload(uniqueId);
            }
        } catch (err) {
            window.toastr?.error?.(err.message);
        } finally {
            setModalLoading(submitButton, false);
        }
    }
</script>