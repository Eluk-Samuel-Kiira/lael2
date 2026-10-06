{{-- resources/views/procurement/expense-template/bulk-log-modal.blade.php --}}
<div class="modal fade" id="bulkLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="ki-duotone ki-basket fs-2 me-2 text-primary"></i>
                    {{ __('auth.bulk_log_expenses') }}
                </h2>
                <button type="button" class="btn btn-icon btn-sm" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>

            <div class="modal-body">

                {{-- ══════════════════════════════════════════════════════ --}}
                {{-- Shared header — date + payment method for all rows      --}}
                {{-- ══════════════════════════════════════════════════════ --}}
                <div class="rounded bg-light p-4 mb-6">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">
                                {{ __('pagination.date') }}
                            </label>
                            <input type="date" id="bulkLogSharedDate"
                                   class="form-control form-control-solid"
                                   value="{{ date('Y-m-d') }}">
                            <small class="text-muted fs-8">
                                {{ __('auth.bulk_log_date_hint') }}
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">
                                {{ __('pagination.payment_method') }}
                            </label>
                            <select id="bulkLogSharedPaymentMethod"
                                    class="form-select form-select-solid"
                                    data-control="select2"
                                    data-dropdown-parent="#bulkLogModal">
                                <option value="">{{ __('payments.select_payment_method') }}</option>

                                {{-- ★ From the AppServiceProvider's shared view data --}}
                                @foreach($active_payment_methods ?? [] as $pm)
                                    <option value="{{ $pm->id }}"
                                        {{ $pm->is_default ? 'selected' : '' }}>
                                        {{ $pm->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted fs-8">
                                {{ __('auth.bulk_log_payment_hint') }}
                            </small>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════════════ --}}
                {{-- Template picker — dropdown filtered by what's already added --}}
                {{-- ══════════════════════════════════════════════════════ --}}
                <div class="mb-6">
                    <label class="fs-6 fw-semibold mb-2">
                        {{ __('auth.add_template') }}
                    </label>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-9">
                            <select id="bulkLogTemplatePicker"
                                    class="form-select form-select-solid"
                                    data-control="select2"
                                    data-placeholder="{{ __('auth.select_template_to_add') }}"
                                    data-dropdown-parent="#bulkLogModal">
                                <option value=""></option>
                                {{-- options populated by JS --}}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100"
                                    id="bulkLogAddBtn"
                                    onclick="bulkLogAddSelected()"
                                    disabled>
                                <i class="ki-duotone ki-plus fs-3 me-1"></i>
                                {{ __('auth.add_template') }}
                            </button>
                        </div>
                    </div>
                    <small class="text-muted fs-8">
                        {{ __('auth.bulk_log_max_10') }}
                    </small>
                </div>

                {{-- ══════════════════════════════════════════════════════ --}}
                {{-- Rows — one per added template                             --}}
                {{-- ══════════════════════════════════════════════════════ --}}
                <div id="bulkLogRows"></div>

                {{-- ══════════════════════════════════════════════════════ --}}
                {{-- Empty state                                              --}}
                {{-- ══════════════════════════════════════════════════════ --}}
                <div id="bulkLogEmptyState" class="text-center text-muted py-10 border border-dashed rounded">
                    <i class="ki-duotone ki-basket fs-4tx mb-3 text-gray-400"></i>
                    <p class="mb-0">{{ __('auth.bulk_log_no_rows') }}</p>
                    <small>{{ __('auth.bulk_log_no_rows_hint') }}</small>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-between">
                <div class="text-muted fs-7">
                    <span class="fw-bold" id="bulkLogFooterCount">0</span>
                    {{ __('auth.bulk_log_items_selected') }}
                    · {{ __('auth.bulk_log_max_10') }}
                </div>
                <div>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('auth._cancel') }}
                    </button>
                    <button type="button" id="bulkLogSubmitBtn"
                            class="btn btn-primary"
                            onclick="submitBulkLog()">
                        <span class="indicator-label">
                            <i class="ki-duotone ki-check fs-3 me-1"></i>
                            {{ __('auth.bulk_log_save_all') }}
                        </span>
                        <span class="indicator-progress" style="display:none;">
                            {{ __('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm ms-2"></span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const MAX_BULK = 10;

    let allTemplates     = [];             // fetched once via AJAX
    let addedTemplateIds = new Set();      // ids currently rendered as rows

    // ─────────────────────────────────────────────────────────────
    // Open modal
    // ─────────────────────────────────────────────────────────────
    window.openBulkLogModal = async function () {
        await loadTemplatesOnce();
        resetModal();
        const modal = new bootstrap.Modal(document.getElementById('bulkLogModal'));
        modal.show();
    };

    // ─────────────────────────────────────────────────────────────
    // Lazy-load the template list (once)
    // ─────────────────────────────────────────────────────────────
    async function loadTemplatesOnce() {
        if (allTemplates.length) return;

        try {
            const res = await fetch('{{ route("expense-templates.available-templates") }}', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message);
            allTemplates = data.templates || [];
        } catch (e) {
            console.error('Failed to load templates', e);
            Swal.fire({
                icon: 'error',
                title: '{{ __("passwords.error") }}',
                text: '{{ __("auth.bulk_log_failed_templates") }}',
                confirmButtonColor: '#009ef7'
            });
            allTemplates = [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Reset modal state on open
    // ─────────────────────────────────────────────────────────────
    function resetModal() {
        addedTemplateIds.clear();

        document.getElementById('bulkLogRows').innerHTML = '';
        document.getElementById('bulkLogEmptyState').style.display = '';
        document.getElementById('bulkLogSharedDate').value = new Date().toISOString().slice(0, 10);
        document.getElementById('bulkLogFooterCount').textContent = '0';

        // Reset payment method to the default option that's already rendered
        const pm = document.getElementById('bulkLogSharedPaymentMethod');
        const defaultOption = pm.querySelector('option[selected]') || pm.options[1];
        pm.value = defaultOption ? defaultOption.value : '';

        if (window.jQuery && window.jQuery(pm).data('select2')) {
            window.jQuery(pm).trigger('change.select2');
        }

        renderPickerOptions();
    }

    // ─────────────────────────────────────────────────────────────
    // Template picker
    // ─────────────────────────────────────────────────────────────
    function renderPickerOptions() {
        const sel = document.getElementById('bulkLogTemplatePicker');
        const currentVal = sel.value;

        sel.innerHTML = '<option value=""></option>';

        allTemplates
            .filter(t => !addedTemplateIds.has(String(t.id)))
            .forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name + (t.category_name ? ` — ${t.category_name}` : '');
                sel.appendChild(opt);
            });

        if (currentVal && !addedTemplateIds.has(String(currentVal))) {
            sel.value = currentVal;
        }

        if (window.jQuery && window.jQuery(sel).data('select2')) {
            window.jQuery(sel).trigger('change.select2');
        }

        updateAddButton();
    }

    function updateAddButton() {
        const sel = document.getElementById('bulkLogTemplatePicker');
        const btn = document.getElementById('bulkLogAddBtn');
        const hasValue   = sel.value !== '';
        const underLimit = addedTemplateIds.size < MAX_BULK;
        btn.disabled = !hasValue || !underLimit;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const sel = document.getElementById('bulkLogTemplatePicker');
        if (!sel) return;

        sel.addEventListener('change', updateAddButton);

        if (window.jQuery) {
            window.jQuery(sel).on('change',          updateAddButton);
            window.jQuery(sel).on('select2:select',  updateAddButton);
            window.jQuery(sel).on('select2:unselect',updateAddButton);
        }
    });

    // ─────────────────────────────────────────────────────────────
    // Add row
    // ─────────────────────────────────────────────────────────────
    window.bulkLogAddSelected = function () {
        const sel = document.getElementById('bulkLogTemplatePicker');
        const id  = sel.value;
        if (!id) return;

        if (addedTemplateIds.size >= MAX_BULK) {
            Swal.fire({
                icon: 'warning',
                title: '{{ __("auth.bulk_log_limit_title") }}',
                text:  '{{ __("auth.bulk_log_limit_text") }}',
                confirmButtonColor: '#009ef7'
            });
            return;
        }

        if (addedTemplateIds.has(String(id))) return;

        const template = allTemplates.find(t => String(t.id) === String(id));
        if (!template) return;

        addedTemplateIds.add(String(id));
        appendRow(template);

        sel.value = '';
        if (window.jQuery && window.jQuery(sel).data('select2')) {
            window.jQuery(sel).trigger('change.select2');
        }

        renderPickerOptions();
        updateFooter();
    };

    // ─────────────────────────────────────────────────────────────
    // Row DOM
    // ─────────────────────────────────────────────────────────────
    function appendRow(t) {
        document.getElementById('bulkLogEmptyState').style.display = 'none';

        const suggested = parseFloat(t.suggested_amount) || 0;

        const row = document.createElement('div');
        row.className = 'card card-flush border border-dashed mb-3 bulk-log-row';
        row.dataset.templateId = t.id;

        row.innerHTML = `
            <div class="card-body py-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <div class="d-flex align-items-center">
                            <span class="bullet bullet-vertical bg-primary me-3" style="height:42px;"></span>
                            <div>
                                <div class="fw-bold text-gray-800">${escapeHtml(t.name)}</div>
                                <div class="text-muted fs-7">
                                    ${escapeHtml(t.category_name || '')}
                                    ${t.supplier_name ? ' · ' + escapeHtml(t.supplier_name) : ''}
                                    ${t.location_name ? ' · ' + escapeHtml(t.location_name) : ''}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="fs-7 fw-semibold mb-1">{{ __('pagination.gross_amount') }}</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">{{ currency_symbol() }}</span>
                            <input type="number" step="0.01" min="0.01"
                                   class="form-control form-control-solid bulk-row-amount"
                                   value="${suggested > 0 ? suggested : ''}"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="fs-7 fw-semibold mb-1">{{ __('pagination.notes') }}</label>
                        <input type="text"
                               class="form-control form-control-solid form-control-sm bulk-row-notes"
                               placeholder="{{ __('auth.optional') }}">
                    </div>

                    <div class="col-md-1 d-flex justify-content-end">
                        <button type="button"
                                class="btn btn-sm btn-light-danger"
                                onclick="bulkLogRemoveRow(this)"
                                title="{{ __('auth._delete') }}">
                            <i class="ki-duotone ki-trash fs-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('bulkLogRows').appendChild(row);
    }

    // ─────────────────────────────────────────────────────────────
    // Remove row — returns template to the picker
    // ─────────────────────────────────────────────────────────────
    window.bulkLogRemoveRow = function (btn) {
        const row = btn.closest('.bulk-log-row');
        if (!row) return;

        addedTemplateIds.delete(String(row.dataset.templateId));
        row.remove();

        if (document.querySelectorAll('.bulk-log-row').length === 0) {
            document.getElementById('bulkLogEmptyState').style.display = '';
        }

        renderPickerOptions();
        updateFooter();
    };

    function updateFooter() {
        document.getElementById('bulkLogFooterCount').textContent =
            document.querySelectorAll('.bulk-log-row').length;
    }

    // ─────────────────────────────────────────────────────────────
    // Submit → validate → SwalFire → POST
    // ─────────────────────────────────────────────────────────────
    window.submitBulkLog = async function () {
        const rows = Array.from(document.querySelectorAll('.bulk-log-row'));

        if (rows.length === 0) {
            Swal.fire({
                icon: 'info',
                title: '{{ __("auth.bulk_log_none_title") }}',
                text:  '{{ __("auth.bulk_log_none_text") }}',
                confirmButtonColor: '#009ef7'
            });
            return;
        }

        const date            = document.getElementById('bulkLogSharedDate').value;
        const paymentMethodId = document.getElementById('bulkLogSharedPaymentMethod').value;

        if (!date) {
            Swal.fire({ icon: 'warning', title: '{{ __("pagination.date_required") }}', confirmButtonColor: '#009ef7' });
            return;
        }
        if (!paymentMethodId) {
            Swal.fire({ icon: 'warning', title: '{{ __("auth.payment_method_required") }}', confirmButtonColor: '#009ef7' });
            return;
        }

        // Collect + validate
        const items = [];
        let invalid = false;

        rows.forEach(row => {
            const templateId = row.dataset.templateId;
            const amount     = parseFloat(row.querySelector('.bulk-row-amount').value) || 0;
            const notes      = row.querySelector('.bulk-row-notes').value.trim();
            const name       = row.querySelector('.fw-bold').textContent.trim();

            if (amount <= 0) invalid = true;

            items.push({ template_id: templateId, template_name: name, gross_amount: amount, notes: notes });
        });

        if (invalid) {
            Swal.fire({
                icon: 'warning',
                title: '{{ __("auth.bulk_log_invalid_amounts_title") }}',
                text:  '{{ __("auth.bulk_log_invalid_amounts_text") }}',
                confirmButtonColor: '#009ef7'
            });
            return;
        }

        // Confirmation table
        const total = items.reduce((s, i) => s + i.gross_amount, 0);
        const html = `
            <div class="text-start">
                <p class="mb-3"><strong>${items.length}</strong> {{ __('auth.bulk_log_confirm_intro') }}</p>
                <table class="table table-sm table-borderless">
                    <tbody>
                        ${items.map(i => `
                            <tr>
                                <td>${escapeHtml(i.template_name)}</td>
                                <td class="text-end fw-bold">
                                    {{ currency_symbol() }}${i.gross_amount.toLocaleString(undefined, {minimumFractionDigits: 2})}
                                </td>
                            </tr>
                        `).join('')}
                        <tr class="border-top">
                            <td class="fw-bold">{{ __('auth.bulk_log_total') }}</td>
                            <td class="text-end fw-bold text-primary">
                                {{ currency_symbol() }}${total.toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="text-muted fs-7">
                    {{ __('pagination.date') }}: <strong>${escapeHtml(date)}</strong>
                </div>
            </div>
        `;

        const { isConfirmed } = await Swal.fire({
            title: '{{ __("auth.bulk_log_confirm_title") }}',
            html: html,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("auth.bulk_log_confirm_yes") }}',
            cancelButtonText:  '{{ __("auth._cancel") }}',
            confirmButtonColor: '#009ef7',
            cancelButtonColor:  '#e4e6ef',
            width: 600,
            reverseButtons: true,
        });

        if (!isConfirmed) return;

        // POST
        const btn   = document.getElementById('bulkLogSubmitBtn');
        const label = btn.querySelector('.indicator-label');
        const prog  = btn.querySelector('.indicator-progress');
        label.style.display = 'none';
        prog.style.display  = 'inline-block';

        try {
            const res = await fetch('{{ route("expense-templates.bulk-log") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    date: date,
                    payment_method_id: paymentMethodId,
                    items: items.map(i => ({
                        template_id:  i.template_id,
                        gross_amount: i.gross_amount,
                        notes:        i.notes,
                    })),
                }),
            });

            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Failed');

            const modal = bootstrap.Modal.getInstance(document.getElementById('bulkLogModal'));
            if (modal) modal.hide();

            await Swal.fire({
                icon: 'success',
                title: '{{ __("passwords.success") }}',
                html: data.message,
                timer: 2500,
                showConfirmButton: false,
            });

            window.location.reload();

        } catch (e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: '{{ __("passwords.error") }}',
                text:  e.message,
                confirmButtonColor: '#009ef7',
            });
        } finally {
            label.style.display = 'inline-block';
            prog.style.display  = 'none';
        }
    };

    // ─────────────────────────────────────────────────────────────
    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
})();
</script>
@endpush