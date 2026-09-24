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

                <button type="button" class="btn btn-sm btn-light-primary mt-3" onclick="addSplitRow()">
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
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
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
const SPLIT_LOCATIONS   = @json($locations->map(fn($l) => ['id' => $l->id, 'name' => $l->name]));
const SPLIT_DEPARTMENTS = @json($departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'location_id' => $d->location_id]));

let splitState = {
    batchId: null,
    batchNumber: '',
    available: 0,
    rows: [],   // each: { id: uid, quantity, location_id, department_id }
};

function openSplitBatchModal(batchId, batchNumber, available) {
    splitState = { batchId, batchNumber, available: parseFloat(available) || 0, rows: [] };

    document.getElementById('splitSourceBatchNumber').textContent = batchNumber;
    document.getElementById('splitSourceAvailable').textContent = splitState.available.toFixed(2);

    // Seed with two empty rows
    addSplitRow();
    addSplitRow();

    const modal = new bootstrap.Modal(document.getElementById('splitBatchModal'));
    modal.show();
}

let splitRowUid = 1;

function addSplitRow() {
    const uid = splitRowUid++;
    splitState.rows.push({ id: uid, quantity: 0, location_id: '', department_id: '' });
    renderSplitRows();
}

function removeSplitRow(uid) {
    splitState.rows = splitState.rows.filter(r => r.id !== uid);
    renderSplitRows();
}

function renderSplitRows() {
    const container = document.getElementById('splitRowsContainer');
    container.innerHTML = splitState.rows.map(row => {
        const deptOptions = SPLIT_DEPARTMENTS
            .filter(d => !row.location_id || String(d.location_id) === String(row.location_id))
            .map(d => `<option value="${d.id}" ${String(d.id) === String(row.department_id) ? 'selected' : ''}>${escapeHtml(d.name)}</option>`)
            .join('');

        const locOptions = SPLIT_LOCATIONS
            .map(l => `<option value="${l.id}" ${String(l.id) === String(row.location_id) ? 'selected' : ''}>${escapeHtml(l.name)}</option>`)
            .join('');

        return `
            <div class="card card-bordered mb-3 split-row" data-uid="${row.id}">
                <div class="card-body py-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label required">${escapeHtml(window.__t_quantity || 'Quantity')}</label>
                            <input type="number" class="form-control split-qty" min="0" step="0.01"
                                   value="${row.quantity || ''}"
                                   oninput="updateSplitRow(${row.id}, 'quantity', this.value)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">${escapeHtml(window.__t_location || 'Location')}</label>
                            <select class="form-select split-loc"
                                    onchange="updateSplitRow(${row.id}, 'location_id', this.value)">
                                <option value="">—</option>
                                ${locOptions}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">${escapeHtml(window.__t_department || 'Department')}</label>
                            <select class="form-select split-dept"
                                    onchange="updateSplitRow(${row.id}, 'department_id', this.value)">
                                <option value="">—</option>
                                ${deptOptions}
                            </select>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-icon btn-light-danger"
                                    onclick="removeSplitRow(${row.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    updateSplitTotal();
}

function updateSplitRow(uid, field, value) {
    const row = splitState.rows.find(r => r.id === uid);
    if (!row) return;

    if (field === 'quantity') {
        row.quantity = parseFloat(value) || 0;
    } else if (field === 'location_id') {
        row.location_id = value;
        // Reset department if it no longer matches the chosen location
        if (row.department_id) {
            const dept = SPLIT_DEPARTMENTS.find(d => String(d.id) === String(row.department_id));
            if (!dept || String(dept.location_id) !== String(value)) {
                row.department_id = '';
            }
        }
        renderSplitRows();
        return;
    } else if (field === 'department_id') {
        row.department_id = value;
        // Auto-set location from department if not chosen yet
        if (!row.location_id) {
            const dept = SPLIT_DEPARTMENTS.find(d => String(d.id) === String(value));
            if (dept) row.location_id = String(dept.location_id);
            renderSplitRows();
            return;
        }
    }
    updateSplitTotal();
}

function updateSplitTotal() {
    const total = splitState.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0);
    document.getElementById('splitTotalQty').textContent = total.toFixed(2);
    const warn = document.getElementById('splitTotalWarning');
    if (total > splitState.available) warn.classList.remove('d-none');
    else warn.classList.add('d-none');
}

document.getElementById('confirmSplitBtn')?.addEventListener('click', function () {
    const btn = this;
    const label = btn.querySelector('.indicator-label');
    const prog  = btn.querySelector('.indicator-progress');

    // Build payload
    const splits = splitState.rows.map(r => ({
        quantity:      parseFloat(r.quantity) || 0,
        location_id:   r.location_id,
        department_id: r.department_id,
    })).filter(r => r.quantity > 0 && r.location_id && r.department_id);

    if (splits.length < 2) {
        toastr.warning('{{ __("passwords.need_at_least_two_splits") }}');
        return;
    }

    const total = splits.reduce((s, r) => s + r.quantity, 0);
    if (total > splitState.available) {
        toastr.error('{{ __("passwords.split_exceeds_available_quantity") }}');
        return;
    }

    btn.disabled = true;
    label.style.display = 'none';
    prog.style.display = 'inline';

    fetch('{{ route("batches.split") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({ batch_id: splitState.batchId, splits }),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        label.style.display = 'inline';
        prog.style.display = 'none';

        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('splitBatchModal'))?.hide();
                if (data.reload) location.reload();
            }, 1000);
        } else {
            toastr.error(data.message || '{{ __("passwords.error") }}');
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        label.style.display = 'inline';
        prog.style.display = 'none';
        toastr.error('{{ __("passwords.error") }}');
    });
});

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

// Preload translations for the split rows
window.__t_quantity   = '{{ __("passwords.quantity") }}';
window.__t_location   = '{{ __("passwords.location") }}';
window.__t_department = '{{ __("auth._department") }}';
</script>
@endpush