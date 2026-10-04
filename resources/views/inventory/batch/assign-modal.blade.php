{{-- ============================================================
     Assign Batches Modal
     - Location → Department cascade (server-fetched)
     - No Select2, no nesting, no duplicate IDs
     ============================================================ --}}

<div class="modal fade" id="assignBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ki-duotone ki-tag fs-2 me-2"></i>
                    {{ __('passwords.assign_batches') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="alert alert-info d-flex align-items-center mb-5">
                    <i class="bi bi-info-circle fs-2 me-3"></i>
                    <div>
                        <span class="fw-bold">
                            {{ __('passwords.selected_batches') }}:
                            <span id="assignBatchSelectedCount">0</span>
                        </span>
                    </div>
                </div>

                {{-- Location --}}
                <div class="mb-5">
                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                        <span class="required">{{ __('pagination._location') }}</span>
                    </label>
                    <select id="assignBatchLocationId"
                            class="form-select form-select-solid">
                        <option value="">{{ __('passwords.select_location') }}</option>
                        @foreach($locations ?? [] as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Department --}}
                <div class="mb-5">
                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                        <span class="required">{{ __('auth._department') }}</span>
                    </label>
                    <select id="assignBatchDepartmentId"
                            class="form-select form-select-solid"
                            disabled>
                        <option value="">{{ __('passwords.select_department') }}</option>
                    </select>
                    <div id="assignBatchDepartmentEmptyHint"
                         class="text-muted fs-8 mt-2 d-none">
                        {{ __('passwords.no_departments_for_location') }}
                    </div>
                </div>

                <div class="alert alert-warning d-flex align-items-center mt-5">
                    <i class="bi bi-exclamation-triangle fs-2 me-3"></i>
                    <div>
                        <span class="fw-bold">{{ __('passwords.assignment_warning') }}</span>
                        <br>
                        <small>{{ __('passwords.assignment_warning_description') }}</small>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('auth._discard') }}
                </button>
                <button type="button" id="assignBatchUnassignBtn" class="btn btn-warning">
                    <span class="indicator-label">{{ __('passwords.unassign') }}</span>
                    <span class="indicator-progress" style="display: none;">
                        {{ __('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
                <button type="button" id="assignBatchConfirmBtn" class="btn btn-primary">
                    <span class="indicator-label">{{ __('passwords.assign_batches') }}</span>
                    <span class="indicator-progress" style="display: none;">
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
(function () {
    'use strict';

    // ── Route template (safely serialized for JS) ──────────────
    const DEPT_URL_TEMPLATE = @json(route('get.departments.by.location', ['locationId' => '__LOC__']));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function departmentsByLocationUrl(locationId) {
        return DEPT_URL_TEMPLATE.replace('__LOC__', encodeURIComponent(locationId));
    }

    // ── State ──────────────────────────────────────────────────
    let selectedBatchIds = [];
    let locationSelect, departmentSelect, emptyHint;

    // ── Load departments for a given location ──────────────────
    async function loadDepartments(locationId) {
        if (!departmentSelect) return;

        // Reset the department select
        departmentSelect.innerHTML = `<option value="">{{ __("passwords.select_department") }}</option>`;
        departmentSelect.disabled  = true;
        emptyHint?.classList.add('d-none');

        if (!locationId) return;

        try {
            const res = await fetch(departmentsByLocationUrl(locationId), {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();

            // Normalize: array | object-with-numeric-keys | nested wrapper
            let list = [];
            if (Array.isArray(data)) {
                list = data;
            } else if (data && typeof data === 'object') {
                const raw = data.departments ?? data.data ?? data;
                if (Array.isArray(raw)) {
                    list = raw;
                } else if (raw && typeof raw === 'object') {
                    list = Object.values(raw);
                }
            }
            list = list.filter(d => d && typeof d === 'object' && d.id);

            list.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                departmentSelect.appendChild(opt);
            });

            departmentSelect.disabled = list.length === 0;
            emptyHint?.classList.toggle('d-none', list.length > 0);
        } catch (err) {
            console.error('[batches] dept fetch failed:', err);
            emptyHint?.classList.remove('d-none');
        }
    }

    // ── Public: open modal ─────────────────────────────────────
    window.openAssignBatchModal = function (batchIds) {
        selectedBatchIds = Array.isArray(batchIds) ? batchIds : [];

        const countEl = document.getElementById('assignBatchSelectedCount');
        if (countEl) countEl.textContent = selectedBatchIds.length;

        // Reset form to pristine state
        if (locationSelect)   locationSelect.value = '';
        if (departmentSelect) {
            departmentSelect.innerHTML = `<option value="">{{ __("passwords.select_department") }}</option>`;
            departmentSelect.disabled  = true;
        }
        emptyHint?.classList.add('d-none');

        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('assignBatchModal')
        ).show();
    };

    // ── Helpers ────────────────────────────────────────────────
    function toggleBusy(btn, busy) {
        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        btn.disabled = busy;
        if (label)    label.style.display    = busy ? 'none' : 'inline';
        if (progress) progress.style.display = busy ? 'inline' : 'none';
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            const text = await res.text();
            throw new Error('HTTP ' + res.status + ' — ' + text);
        }
        return res.json();
    }

    // ── Assign handler ─────────────────────────────────────────
    async function handleAssign(btn) {
        const locationId   = locationSelect?.value   || null;
        const departmentId = departmentSelect?.value || null;

        if (!locationId && !departmentId) {
            toastr.warning('{{ __("passwords.at_least_one_assignment_required") }}');
            return;
        }
        if (!selectedBatchIds.length) {
            toastr.warning('{{ __("passwords.no_batches_selected") }}');
            return;
        }

        toggleBusy(btn, true);

        try {
            const data = await postJson('{{ route("batches.assign") }}', {
                batch_ids:     selectedBatchIds,
                location_id:   locationId,
                department_id: departmentId,
            });

            if (data.success) {
                toastr.success(data.message);
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('assignBatchModal'))?.hide();
                    if (data.reload) location.reload();
                }, 800);
            } else {
                toastr.error(data.message || '{{ __("passwords.error_assigning_batches") }}');
            }
        } catch (err) {
            console.error('[batches] assign failed:', err);
            toastr.error('{{ __("passwords.error_assigning_batches") }}');
        } finally {
            toggleBusy(btn, false);
        }
    }

    // ── Unassign handler ───────────────────────────────────────
    async function handleUnassign(btn) {
        if (!confirm('{{ __("passwords.confirm_unassign_batches") }}')) return;

        toggleBusy(btn, true);

        try {
            const data = await postJson('{{ route("batches.unassign") }}', {
                batch_ids: selectedBatchIds,
            });

            if (data.success) {
                toastr.success(data.message);
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('assignBatchModal'))?.hide();
                    if (data.reload) location.reload();
                }, 800);
            } else {
                toastr.error(data.message || '{{ __("passwords.error_unassigning_batches") }}');
            }
        } catch (err) {
            console.error('[batches] unassign failed:', err);
            toastr.error('{{ __("passwords.error_unassigning_batches") }}');
        } finally {
            toggleBusy(btn, false);
        }
    }

    // ── Init: wire listeners once DOM is ready ─────────────────
    function init() {
        locationSelect   = document.getElementById('assignBatchLocationId');
        departmentSelect = document.getElementById('assignBatchDepartmentId');
        emptyHint        = document.getElementById('assignBatchDepartmentEmptyHint');

        // Modal not on this page — nothing to wire up
        if (!locationSelect || !departmentSelect) return;

        // Cascade: location → departments
        locationSelect.addEventListener('change', function () {
            loadDepartments(this.value);
        });

        // Confirm / unassign
        document.getElementById('assignBatchConfirmBtn')
            ?.addEventListener('click', function () { handleAssign(this); });

        document.getElementById('assignBatchUnassignBtn')
            ?.addEventListener('click', function () { handleUnassign(this); });

        // Reset state on modal close
        document.getElementById('assignBatchModal')
            ?.addEventListener('hidden.bs.modal', function () {
                locationSelect.value = '';
                departmentSelect.innerHTML = `<option value="">{{ __("passwords.select_department") }}</option>`;
                departmentSelect.disabled  = true;
                emptyHint.classList.add('d-none');

                const countEl = document.getElementById('assignBatchSelectedCount');
                if (countEl) countEl.textContent = '0';
                selectedBatchIds = [];
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush