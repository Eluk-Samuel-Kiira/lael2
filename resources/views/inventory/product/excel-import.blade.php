{{-- ═══════════════════════════════════════════════════════════════════════════
     inventory/import/modal.blade.php
     Include this file ONCE inside any page that needs the import button.
     ═══════════════════════════════════════════════════════════════════════════ --}}

@can('create product')

{{-- ── TRIGGER BUTTON (drop this wherever you need it in the toolbar) ────────
     Already wired into product-index — copy the snippet below to other pages
     ─────────────────────────────────────────────────────────────────────────
     <button type="button"
             class="btn btn-light-success flex-shrink-0"
             data-bs-toggle="modal"
             data-bs-target="#kt_modal_catalog_import">
         <i class="ki-duotone ki-file-up fs-2 me-2"></i>
         <span class="d-none d-sm-inline">{{ __('Import Excel') }}</span>
         <span class="d-inline d-sm-none">Import</span>
     </button>
     ───────────────────────────────────────────────────────────────────────── --}}

{{-- ── MODAL ──────────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="kt_modal_catalog_import" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">

            {{-- Header --}}
            <div class="modal-header bg-primary py-4">
                <h3 class="modal-title text-white fw-bold fs-4 d-flex align-items-center gap-2">
                    <i class="ki-duotone ki-file-up fs-1 text-white">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    {{ __('pagination.import_modal_title') }}
                </h3>
                <button type="button"
                        class="btn-close btn-close-white d-none"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body px-8 py-6">

                {{-- Step indicator --}}
                <div class="d-flex align-items-center gap-3 mb-6">
                    <div class="d-flex flex-column align-items-center">
                        <div class="w-35px h-35px rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold fs-6">1</div>
                        <span class="text-muted fs-8 mt-1">{{ __('pagination.import_step_download') }}</span>
                    </div>
                    <div class="flex-grow-1 border-top border-dashed border-primary opacity-50"></div>
                    <div class="d-flex flex-column align-items-center">
                        <div class="w-35px h-35px rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold fs-6">2</div>
                        <span class="text-muted fs-8 mt-1">{{ __('pagination.import_step_fill') }}</span>
                    </div>
                    <div class="flex-grow-1 border-top border-dashed border-primary opacity-50"></div>
                    <div class="d-flex flex-column align-items-center">
                        <div class="w-35px h-35px rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold fs-6">3</div>
                        <span class="text-muted fs-8 mt-1">{{ __('pagination.import_step_upload') }}</span>
                    </div>
                </div>

                {{-- Download template --}}
                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-5">
                    <i class="ki-duotone ki-information-5 fs-2tx text-primary me-4">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <div class="d-flex flex-stack flex-grow-1">
                        <div class="fw-semibold">
                            <h4 class="text-gray-900 fw-bold fs-6 mb-1">{{ __('pagination.import_step1_heading') }}</h4>
                            <div class="fs-7 text-gray-700">
                                {{ __('pagination.import_step1_desc') }}
                                <strong>{{ __('pagination.import_sheet_order') }}</strong>.
                                {{ __('pagination.import_step1_tip') }}
                            </div>
                        </div>
                        <a href="{{ route('catalog.import.template') }}"
                           class="btn btn-sm btn-primary ms-4 flex-shrink-0">
                            <i class="ki-duotone ki-arrow-down fs-4 me-1">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            {{ __('pagination.import_template_btn') }}
                        </a>
                    </div>
                </div>

                {{-- Upload form --}}
                <form id="importCatalogForm" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-5">
                        <label class="form-label fw-semibold required">
                            {{ __('pagination.import_step3_label') }}
                        </label>

                        {{-- Drop-zone --}}
                        <div id="importDropZone"
                             class="border border-2 border-dashed border-gray-300 rounded p-6 text-center cursor-pointer position-relative"
                             onclick="document.getElementById('importFileInput').click()"
                             ondragover="event.preventDefault(); this.classList.add('border-primary','bg-light-primary')"
                             ondragleave="this.classList.remove('border-primary','bg-light-primary')"
                             ondrop="handleImportDrop(event)">

                            <i class="ki-duotone ki-file-up fs-3x text-primary mb-3">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <div class="fw-semibold text-gray-700 mb-1">
                                {{ __('pagination.import_dropzone_title') }}
                            </div>
                            <div class="text-muted fs-7">
                                {{ __('pagination.import_dropzone_or') }} <span class="text-primary fw-bold">{{ __('pagination.import_dropzone_browse') }}</span> — {{ __('pagination.import_dropzone_hint') }}
                            </div>
                            <div id="importFileName" class="mt-3 fw-semibold text-success d-none"></div>
                        </div>
                        <input type="file"
                               id="importFileInput"
                               name="excel_file"
                               accept=".xlsx,.xls"
                               class="d-none"
                               onchange="previewImportFile(this)">
                        <div class="text-danger fs-8 mt-1 d-none" id="importFileError"></div>
                    </div>

                    {{-- Progress bar (hidden until upload starts) --}}
                    <div id="importProgress" class="d-none mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted fs-7">{{ __('pagination.import_processing') }}</span>
                            <span class="text-muted fs-7" id="importProgressPct">0%</span>
                        </div>
                        <div class="progress h-6px">
                            <div id="importProgressBar"
                                 class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                 style="width: 0%"></div>
                        </div>
                    </div>
                </form>

                {{-- Result panel (hidden until response arrives) --}}
                <div id="importResultPanel" class="d-none">
                    <div class="separator my-4"></div>
                    <h5 class="fw-bold text-gray-800 mb-4">{{ __('pagination.import_report_heading') }}</h5>
                    <div id="importResultContent"></div>
                </div>

            </div>{{-- /modal-body --}}

            {{-- Footer --}}
            <div class="modal-footer py-4">
                <button type="button"
                        id="importCancelBtn"
                        class="btn btn-light me-3"
                        data-bs-dismiss="modal">
                    {{ __('pagination.import_btn_cancel') }}
                </button>
                <button type="button"
                        id="importSubmitBtn"
                        class="btn btn-primary"
                        onclick="submitCatalogImport()">
                    <span id="importBtnLabel">
                        <i class="ki-duotone ki-arrow-up fs-4 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        {{ __('pagination.import_btn_submit') }}
                    </span>
                    <span id="importBtnSpinner" class="d-none">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        {{ __('pagination.import_btn_importing') }}
                    </span>
                </button>
            </div>

        </div>{{-- /modal-content --}}
    </div>
</div>

@endcan