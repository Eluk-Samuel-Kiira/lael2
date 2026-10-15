{{-- resources/views/manufacturing/production-order/edit-modal-shared.blade.php --}}

{{-- ═══════════════════════════════════════════════════════════════════
     SHARED EDIT PRODUCTION ORDER MODAL

     One instance, present in the page once. Populated by
     window.POEdit.openForOrder(orderId) at runtime.

     Every element that the JS references is tagged with a po-edit-*
     class or a data-po-* attribute. Renaming any of them will break
     the module — grep for the class name in edit-modal-scripts.blade.php
     before changing anything.
     ═══════════════════════════════════════════════════════════════════ --}}

<div class="modal fade"
     id="editProductionModalShared"
     tabindex="-1"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-order-id=""
     data-location-id="">

    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">

            {{-- ─── HEADER ───────────────────────────────────────────── --}}
            <div class="modal-header">
                <h2 class="fw-bold d-flex align-items-center">
                    <i class="ki-duotone ki-pencil fs-2 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span class="po-edit-title">
                        {{ __('passwords.edit_production_order') }}
                    </span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            {{-- ─── BODY ─────────────────────────────────────────────── --}}
            <div class="modal-body px-5 my-7">
                <form class="form po-edit-form">
                    @csrf

                    {{-- ─── BASIC INFORMATION ──────────────────────────── --}}
                    <div class="row g-9 mb-8">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('passwords.location') }}</label>
                            <input type="text"
                                   class="form-control form-control-solid po-edit-location"
                                   disabled
                                   value="">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('passwords.scheduled_date') }}</label>
                            <input type="datetime-local"
                                   class="form-control form-control-solid"
                                   name="scheduled_date"
                                   value="">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('auth._status') }}</label>
                            <div class="d-flex align-items-center h-100">
                                <span class="badge po-edit-status fw-bold fs-6"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">{{ __('passwords.notes') }}</label>
                            <textarea class="form-control form-control-solid"
                                      name="notes"
                                      rows="2"
                                      placeholder="{{ __('passwords.optional_notes') }}"></textarea>
                        </div>
                    </div>

                    {{-- ─── INPUT MATERIALS ────────────────────────────── --}}
                    <div class="border rounded p-4 mb-8">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold">
                                <i class="bi bi-box-arrow-in-down me-2"></i>
                                {{ __('passwords.input_materials') }}
                            </h4>
                            <button type="button"
                                    class="btn btn-sm btn-light-primary"
                                    data-po-add-input>
                                <i class="ki-duotone ki-plus fs-3"></i>
                                {{ __('passwords._add_material') }}
                            </button>
                        </div>

                        <div class="po-edit-inputs-container">
                            {{-- JS renders .production-input-item rows here --}}
                        </div>

                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-semibold">{{ __('passwords.total_input_cost') }}:</span>
                                    <span class="po-edit-input-total">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── OUTPUT PRODUCTS ────────────────────────────── --}}
                    <div class="border rounded p-4 mb-8">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold">
                                <i class="bi bi-box-arrow-out me-2"></i>
                                {{ __('passwords.output_products') }}
                            </h4>
                            <button type="button"
                                    class="btn btn-sm btn-light-primary"
                                    data-po-add-output>
                                <i class="ki-duotone ki-plus fs-3"></i>
                                {{ __('passwords._add_product') }}
                            </button>
                        </div>

                        <div class="po-edit-outputs-container">
                            {{-- JS renders .production-output-item rows here --}}
                        </div>

                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-md-8"></div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-semibold">{{ __('passwords.total_output_cost') }}:</span>
                                    <span class="po-edit-output-total">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── GRAND SUMMARY ──────────────────────────────── --}}
                    <div class="card card-flush bg-light-primary mb-6">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_input_cost') }}</span>
                                        <span class="fw-bold fs-3 text-danger po-edit-grand-input">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_output_cost') }}</span>
                                        <span class="fw-bold fs-3 text-success po-edit-grand-output">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_cost') }}</span>
                                        <span class="fw-bold fs-3 text-primary po-edit-grand-cost">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── IN-PROGRESS WARNING (JS toggles visibility) ── --}}
                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 d-none"
                         data-po-in-progress-warning>
                        <i class="ki-duotone ki-information-5 fs-2tx text-warning me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-semibold">
                                <h6 class="text-gray-900 fw-bold mb-1">{{ __('passwords.warning') }}</h6>
                                <div class="fs-7 text-gray-700">
                                    {{ __('passwords.edit_in_progress_warning') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── ACTION BUTTONS ─────────────────────────────── --}}
                    <div class="text-center pt-10">
                        <button type="button"
                                class="btn btn-light me-3"
                                data-bs-dismiss="modal">
                            {{ __('passwords._discard') }}
                        </button>

                        <button type="button"
                                class="btn btn-primary po-edit-submit">
                            <span class="indicator-label">
                                <i class="ki-duotone ki-check fs-2 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ __('passwords.save_changes') }}
                            </span>
                            <span class="indicator-progress" style="display: none;">
                                {{ __('passwords.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>