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

{{-- ── JAVASCRIPT ──────────────────────────────────────────────────────────── --}}
<script>
{{-- Expose translations to JS --}}
window._importLang = {
    selectFile      : '{{ __('pagination.import_js_select_file') }}',
    invalidType     : '{{ __('pagination.import_js_invalid_type') }}',
    networkError    : '{{ __('pagination.import_js_network_error') }}',
    importFailed    : '{{ __('pagination.import_js_import_failed') }}',
    filePrefix      : '{{ __('pagination.import_js_file_prefix') }}',
    statCreated     : '{{ __('pagination.import_js_created') }}',
    statSkipped     : '{{ __('pagination.import_js_skipped') }}',
    statErrors      : '{{ __('pagination.import_js_errors') }}',
    issuesSuffix    : '{{ __('pagination.import_js_issues_suffix') }}',
    sections: {
        categories    : '{{ __('pagination.import_section_categories') }}',
        sub_categories: '{{ __('pagination.import_section_sub_categories') }}',
        products      : '{{ __('pagination.import_section_products') }}',
        variants      : '{{ __('pagination.import_section_variants') }}',
    },
};

(function () {
    'use strict';

    const lang = window._importLang;

    /* ── File preview ─────────────────────────────────────────────────────── */
    window.previewImportFile = function (input) {
        const file = input.files[0];
        if (!file) return;
        showImportFileName(file.name);
    };

    window.handleImportDrop = function (event) {
        event.preventDefault();
        const zone = document.getElementById('importDropZone');
        zone.classList.remove('border-primary', 'bg-light-primary');

        const file = event.dataTransfer.files[0];
        if (!file) return;

        const input = document.getElementById('importFileInput');
        const dt    = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;

        showImportFileName(file.name);
    };

    function showImportFileName (name) {
        const label = document.getElementById('importFileName');
        label.textContent = lang.filePrefix + ' ' + name;
        label.classList.remove('d-none');
        hideError();
        resetResultPanel();
    }

    /* ── Submit ───────────────────────────────────────────────────────────── */
    window.submitCatalogImport = async function () {
        const input = document.getElementById('importFileInput');
        if (!input.files.length) {
            showError(lang.selectFile);
            return;
        }

        const ext = input.files[0].name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(ext)) {
            showError(lang.invalidType);
            return;
        }

        setLoading(true);
        resetResultPanel();

        const form     = document.getElementById('importCatalogForm');
        const formData = new FormData(form);

        try {
            animateProgress();

            const response = await fetch('{{ route('catalog.import.store') }}', {
                method : 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept'      : 'application/json',
                },
                body: formData,
            });

            const data = await response.json();
            stopProgress();

            if (data.success) {
                renderReport(data.report, data.message);
            } else {
                renderError(data.message || lang.importFailed);
            }
        } catch (err) {
            stopProgress();
            renderError(lang.networkError + ': ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    /* ── Progress animation ───────────────────────────────────────────────── */
    let _progressInterval = null;
    let _progress = 0;

    function animateProgress () {
        _progress = 0;
        document.getElementById('importProgress').classList.remove('d-none');
        _progressInterval = setInterval(() => {
            _progress = Math.min(_progress + Math.random() * 8, 88);
            setProgressBar(_progress);
        }, 300);
    }

    function stopProgress () {
        clearInterval(_progressInterval);
        setProgressBar(100);
        setTimeout(() => {
            document.getElementById('importProgress').classList.add('d-none');
            setProgressBar(0);
        }, 600);
    }

    function setProgressBar (val) {
        const pct = Math.round(val);
        document.getElementById('importProgressBar').style.width = pct + '%';
        document.getElementById('importProgressPct').textContent  = pct + '%';
    }

    /* ── Report renderer ──────────────────────────────────────────────────── */
    function renderReport (report, message) {
        const panel   = document.getElementById('importResultPanel');
        const content = document.getElementById('importResultContent');

        let html = `<div class="alert alert-success d-flex align-items-center mb-4">
            <i class="ki-duotone ki-check-circle fs-2x text-success me-3">
                <span class="path1"></span><span class="path2"></span>
            </i>
            <div class="fw-semibold">${escHtml(message)}</div>
        </div>`;

        const sections = [
            { key: 'categories',     color: 'primary' },
            { key: 'sub_categories', color: 'success' },
            { key: 'products',       color: 'warning' },
            { key: 'variants',       color: 'info'    },
        ];

        html += '<div class="row g-3 mb-4">';
        sections.forEach(s => {
            const stat  = report[s.key] || { created: 0, skipped: 0, errors: [] };
            const label = lang.sections[s.key] || s.key;
            html += `
            <div class="col-sm-6 col-xl-3">
                <div class="card border h-100">
                    <div class="card-body p-4">
                        <div class="fw-bold text-gray-700 mb-2 fs-7">${escHtml(label)}</div>
                        <div class="d-flex gap-4">
                            <div>
                                <div class="fw-bolder fs-2 text-success">${stat.created}</div>
                                <div class="text-muted fs-8">${escHtml(lang.statCreated)}</div>
                            </div>
                            <div>
                                <div class="fw-bolder fs-2 text-warning">${stat.skipped}</div>
                                <div class="text-muted fs-8">${escHtml(lang.statSkipped)}</div>
                            </div>
                            <div>
                                <div class="fw-bolder fs-2 text-danger">${stat.errors.length}</div>
                                <div class="text-muted fs-8">${escHtml(lang.statErrors)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        });
        html += '</div>';

        sections.forEach(s => {
            const errors = (report[s.key] || {}).errors || [];
            if (!errors.length) return;
            const label = lang.sections[s.key] || s.key;
            html += `
            <div class="mb-3">
                <div class="fw-bold text-danger mb-2">⚠ ${escHtml(label)} — ${errors.length} ${escHtml(lang.issuesSuffix)}</div>
                <ul class="text-danger fs-7 mb-0 ps-4">
                    ${errors.map(e => `<li>${escHtml(e)}</li>`).join('')}
                </ul>
            </div>`;
        });

        content.innerHTML = html;
        panel.classList.remove('d-none');

        const totalCreated = Object.values(report).reduce((sum, s) => sum + (s.created || 0), 0);
        if (totalCreated > 0) {
            setTimeout(() => {
                reloadComponent('reloadProductComponent', '{{ route('products.index') }}');
            }, 1500);
        }
    }

    function renderError (msg) {
        const panel   = document.getElementById('importResultPanel');
        const content = document.getElementById('importResultContent');
        content.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center">
                <i class="ki-duotone ki-cross-circle fs-2x text-danger me-3">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <div class="fw-semibold">${escHtml(msg)}</div>
            </div>`;
        panel.classList.remove('d-none');
    }

    /* ── Helpers ──────────────────────────────────────────────────────────── */
    function setLoading (loading) {
        document.getElementById('importBtnLabel').classList.toggle('d-none',  loading);
        document.getElementById('importBtnSpinner').classList.toggle('d-none', !loading);
        document.getElementById('importSubmitBtn').disabled = loading;
        document.getElementById('importCancelBtn').disabled = loading;
    }

    function showError (msg) {
        const el = document.getElementById('importFileError');
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function hideError () {
        document.getElementById('importFileError').classList.add('d-none');
    }

    function resetResultPanel () {
        document.getElementById('importResultPanel').classList.add('d-none');
        document.getElementById('importResultContent').innerHTML = '';
    }

    function escHtml (str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    document.getElementById('kt_modal_catalog_import')
            .addEventListener('hidden.bs.modal', function () {
                document.getElementById('importCatalogForm').reset();
                document.getElementById('importFileName').classList.add('d-none');
                resetResultPanel();
                hideError();
                setLoading(false);
            });
})();
</script>

@endcan