{{-- ============================================================
     Split Batch Modal
     - Rows added/removed dynamically
     - Departments fetched lazily per row by location
     - Vanilla JS only
     ============================================================ --}}

<div class="modal fade" id="splitBatchModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-scissors me-2"></i>
                    {{ __('passwords.split_batch') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="alert alert-info d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle fs-2 me-3"></i>
                    <div>
                        <div>{{ __('passwords.split_batch_instruction') }}</div>
                        <div class="mt-1">
                            <strong>{{ __('passwords.source_batch') }}:</strong>
                            <span id="splitSourceBatchNumber" class="badge badge-light-primary"></span>
                            <span class="ms-3">
                                <strong>{{ __('passwords.available') }}:</strong>
                                <span id="splitSourceAvailable" class="badge badge-light-success">0</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div id="splitRowsContainer"></div>

                <button type="button" class="btn btn-sm btn-light-primary mt-3" onclick="SplitBatch.addRow()">
                    <i class="bi bi-plus-lg me-1"></i>
                    {{ __('passwords.add_split') }}
                </button>

                <div class="alert alert-warning d-flex align-items-center mt-4">
                    <i class="bi bi-exclamation-triangle fs-2 me-3"></i>
                    <div>
                        <strong>{{ __('passwords.split_total') }}:</strong>
                        <span id="splitTotalQty">0</span>
                        <span id="splitTotalWarning" class="text-danger ms-2 d-none">
                            ({{ __('passwords.exceeds_available') }})
                        </span>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('auth._discard') }}
                </button>
                <button type="button" id="confirmSplitBtn" class="btn btn-primary">
                    <span class="indicator-label">{{ __('passwords.split_batch') }}</span>
                    <span class="indicator-progress" style="display:none;">
                        {{ __('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
window.SplitBatch = (function () {
    'use strict';

    // ── Config ─────────────────────────────────────────────────
    const ROUTES = {
        departments: '{{ route("get.departments.by.location", ["locationId" => "__LOC__"]) }}',
        split:       '{{ route("batches.split") }}',
    };
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Locations come from the page (small list, always needed)
    const LOCATIONS = @json($locations->map(fn($l) => ['id' => $l->id, 'name' => $l->name]));

    // ── State ──────────────────────────────────────────────────
    const state = {
        batchId: null,
        batchNumber: '',
        available: 0,
        rows: [],           // { id, quantity, location_id, department_id, departments: [] }
    };
    let rowUid = 1;

    // ── Public: open modal ─────────────────────────────────────
    function open(batchId, batchNumber, available) {
        state.batchId     = batchId;
        state.batchNumber = batchNumber;
        state.available   = parseFloat(available) || 0;
        state.rows        = [];
        rowUid            = 1;

        document.getElementById('splitSourceBatchNumber').textContent = batchNumber;
        document.getElementById('splitSourceAvailable').textContent    = state.available.toFixed(2);

        // Seed with two blank rows
        addRow();
        addRow();

        bootstrap.Modal
            .getOrCreateInstance(document.getElementById('splitBatchModal'))
            .show();
    }

    // ── Row management ─────────────────────────────────────────
    function addRow() {
        const id = rowUid++;
        state.rows.push({
            id,
            quantity: 0,
            location_id: '',
            department_id: '',
            departments: [],   // populated lazily
        });
        render();
    }

    function removeRow(id) {
        if (state.rows.length <= 2) {
            toastr.warning('{{ __("passwords.need_at_least_two_splits") }}');
            return;
        }
        state.rows = state.rows.filter(r => r.id !== id);
        render();
    }

    function updateRow(id, field, value) {
        const row = state.rows.find(r => r.id === id);
        if (!row) return;

        if (field === 'quantity') {
            row.quantity = parseFloat(value) || 0;
        }

        if (field === 'location_id') {
            row.location_id = value;

            // Cascade reset: clear department if it doesn't belong to the new location
            if (row.department_id) {
                const dept = row.departments.find(d => String(d.id) === String(row.department_id));
                if (!dept || String(dept.location_id) !== String(value)) {
                    row.department_id = '';
                }
            }

            // Fetch departments for this location, then re-render
            fetchDepartmentsFor(row, value).then(() => render());
            return;
        }

        if (field === 'department_id') {
            row.department_id = value;

            // Auto-fill location from department if it's empty
            if (!row.location_id) {
                const dept = row.departments.find(d => String(d.id) === String(value));
                if (dept) {
                    row.location_id = String(dept.location_id);
                    // Also populate the department list in case it was empty
                    fetchDepartmentsFor(row, row.location_id).then(() => render());
                    return;
                }
            }
        }

        updateTotal();
    }

    // ── Departments per row (lazy) ─────────────────────────────
    async function fetchDepartmentsFor(row, locationId) {
        if (!locationId) {
            row.departments = [];
            return;
        }

        try {
            const url = ROUTES.departments.replace('__LOC__', encodeURIComponent(locationId));
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();
            row.departments = Array.isArray(data) ? data : Object.values(data || {});
        } catch (err) {
            console.warn('[split] dept fetch failed:', err);
            row.departments = [];
        }
    }

    // ── Render ─────────────────────────────────────────────────
    function render() {
        const container = document.getElementById('splitRowsContainer');
        container.innerHTML = state.rows.map(row => {
            const locOptions = LOCATIONS.map(l =>
                `<option value="${l.id}" ${String(l.id) === String(row.location_id) ? 'selected' : ''}>${escapeHtml(l.name)}</option>`
            ).join('');

            const deptOptions = row.departments.map(d =>
                `<option value="${d.id}" ${String(d.id) === String(row.department_id) ? 'selected' : ''}>${escapeHtml(d.name)}</option>`
            ).join('');

            const deptDisabled = !row.location_id || row.departments.length === 0;

            return `
                <div class="card card-bordered mb-3 split-row" data-uid="${row.id}">
                    <div class="card-body py-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label required">${t('quantity')}</label>
                                <input type="number" class="form-control split-qty"
                                       min="0" step="0.01"
                                       value="${row.quantity || ''}"
                                       oninput="SplitBatch.updateRow(${row.id}, 'quantity', this.value)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">${t('location')}</label>
                                <select class="form-select split-loc"
                                        onchange="SplitBatch.updateRow(${row.id}, 'location_id', this.value)">
                                    <option value="">—</option>
                                    ${locOptions}
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">${t('department')}</label>
                                <select class="form-select split-dept"
                                        ${deptDisabled ? 'disabled' : ''}
                                        onchange="SplitBatch.updateRow(${row.id}, 'department_id', this.value)">
                                    <option value="">—</option>
                                    ${deptOptions}
                                </select>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button"
                                        class="btn btn-sm btn-icon btn-light-danger"
                                        onclick="SplitBatch.removeRow(${row.id})"
                                        ${state.rows.length <= 2 ? 'disabled' : ''}>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        updateTotal();
    }

    // ── Total + warning ────────────────────────────────────────
    function updateTotal() {
        const total = state.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0);
        document.getElementById('splitTotalQty').textContent = total.toFixed(2);

        const warn = document.getElementById('splitTotalWarning');
        if (total > state.available) warn.classList.remove('d-none');
        else                          warn.classList.add('d-none');
    }

    // ── Submit ─────────────────────────────────────────────────
    async function submit() {
        const btn   = document.getElementById('confirmSplitBtn');
        const label = btn.querySelector('.indicator-label');
        const prog  = btn.querySelector('.indicator-progress');

        const splits = state.rows
            .map(r => ({
                quantity:      parseFloat(r.quantity) || 0,
                location_id:   r.location_id,
                department_id: r.department_id,
            }))
            .filter(r => r.quantity > 0 && r.location_id && r.department_id);

        if (splits.length < 2) {
            toastr.warning('{{ __("passwords.need_at_least_two_splits") }}');
            return;
        }

        const total = splits.reduce((s, r) => s + r.quantity, 0);
        if (total > state.available) {
            toastr.error('{{ __("passwords.split_exceeds_available_quantity") }}');
            return;
        }

        btn.disabled = true;
        label.style.display = 'none';
        prog.style.display  = 'inline';

        try {
            const res = await fetch(ROUTES.split, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ batch_id: state.batchId, splits }),
            });

            const data = await res.json();

            if (data.success) {
                toastr.success(data.message);

                setTimeout(() => {
                    bootstrap.Modal
                        .getInstance(document.getElementById('splitBatchModal'))
                        ?.hide();

                    window.BatchPage?.reload?.();
                }, 500);
            } else {
                toastr.error(data.message || '{{ __("passwords.error") }}');
            }
        } catch (err) {
            console.error('[split] failed:', err);
            toastr.error('{{ __("passwords.error") }}');
        } finally {
            btn.disabled = false;
            label.style.display = 'inline';
            prog.style.display  = 'none';
        }
    }

    // ── Utilities ──────────────────────────────────────────────
    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function t(key) {
        const map = {
            quantity:   '{{ __("passwords.quantity") }}',
            location:   '{{ __("passwords.location") }}',
            department: '{{ __("auth._department") }}',
        };
        return escapeHtml(map[key] || key);
    }

    // ── Init ───────────────────────────────────────────────────
    function init() {
        document.getElementById('confirmSplitBtn')
            ?.addEventListener('click', submit);

        document.getElementById('splitBatchModal')
            ?.addEventListener('hidden.bs.modal', function () {
                state.batchId = null;
                state.rows    = [];
                document.getElementById('splitRowsContainer').innerHTML = '';
                document.getElementById('splitTotalQty').textContent = '0';
                document.getElementById('splitTotalWarning').classList.add('d-none');
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ── Public API ─────────────────────────────────────────────
    return { open, addRow, removeRow, updateRow };
})();

// Legacy global entry — the component.blade.php calls this name
window.openSplitBatchModal = function (batchId, batchNumber, available) {
    window.SplitBatch.open(batchId, batchNumber, available);
};
</script>
@endpush