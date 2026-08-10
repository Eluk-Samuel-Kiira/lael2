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
                <div class="alert alert-info d-flex align-items-center">
                    <i class="bi bi-info-circle fs-2 me-3"></i>
                    <div>
                        <span class="fw-bold">{{ __('passwords.selected_batches') }}: <span id="selectedCount">0</span></span>
                    </div>
                </div>

                <!-- Location Dropdown -->
                <div class="mb-5">
                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                        <span class="required">{{ __('pagination._location') }}</span>
                    </label>
                    <select id="assignLocationId" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('passwords.select_location') }}">
                        <option value="">{{ __('passwords.select_location') }}</option>
                        @foreach($locations ?? [] as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Department Dropdown (filtered by location via JS) -->
                <div class="mb-5">
                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                        <span class="required">{{ __('auth._department') }}</span>
                    </label>
                    <select id="assignDepartmentId" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('passwords.select_department') }}">
                        <option value="">{{ __('passwords.select_department') }}</option>
                        @foreach($departments ?? [] as $department)
                            <option value="{{ $department->id }}" data-location-id="{{ $department->location_id ?? '' }}">
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
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
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                <button type="button" id="unassignBatchesBtn" class="btn btn-warning">
                    <span class="indicator-label">{{ __('passwords.unassign') }}</span>
                    <span class="indicator-progress" style="display: none;">
                        {{ __('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
                <button type="button" id="assignBatchesBtn" class="btn btn-primary">
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
let selectedBatchIds = [];

// Filter departments based on selected location
document.addEventListener('DOMContentLoaded', function() {
    const locationSelect = document.getElementById('assignLocationId');
    const departmentSelect = document.getElementById('assignDepartmentId');

    if (locationSelect && departmentSelect) {
        locationSelect.addEventListener('change', function() {
            const selectedLocation = this.value;
            const departments = departmentSelect.querySelectorAll('option');

            departments.forEach(function(option) {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }
                const deptLocationId = option.getAttribute('data-location-id');
                if (selectedLocation === '' || deptLocationId === selectedLocation) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });

            // Reset department selection
            departmentSelect.value = '';
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $(departmentSelect).trigger('change');
            }
        });
    }
});

function openAssignBatchModal(batchIds) {
    selectedBatchIds = batchIds;
    document.getElementById('selectedCount').textContent = batchIds.length;
    const modal = new bootstrap.Modal(document.getElementById('assignBatchModal'));
    modal.show();
}

document.getElementById('assignBatchesBtn')?.addEventListener('click', function() {
    const submitBtn = this;
    const indicatorLabel = submitBtn.querySelector('.indicator-label');
    const indicatorProgress = submitBtn.querySelector('.indicator-progress');

    const locationId = document.getElementById('assignLocationId')?.value;
    const departmentId = document.getElementById('assignDepartmentId')?.value;

    if (!locationId && !departmentId) {
        toastr.warning('{{ __("passwords.at_least_one_assignment_required") }}');
        return;
    }

    submitBtn.disabled = true;
    indicatorLabel.style.display = 'none';
    indicatorProgress.style.display = 'inline';

    fetch('{{ route("batches.assign") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            batch_ids: selectedBatchIds,
            location_id: locationId || null,
            department_id: departmentId || null
        })
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        indicatorLabel.style.display = 'inline';
        indicatorProgress.style.display = 'none';

        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('assignBatchModal'));
                if (modal) modal.hide();
                if (data.reload) location.reload();
            }, 1000);
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        console.error('Error assigning batches:', error);
        submitBtn.disabled = false;
        indicatorLabel.style.display = 'inline';
        indicatorProgress.style.display = 'none';
        toastr.error('{{ __("passwords.error_assigning_batches") }}');
    });
});

document.getElementById('unassignBatchesBtn')?.addEventListener('click', function() {
    if (!confirm('{{ __("passwords.confirm_unassign_batches") }}')) return;

    const submitBtn = this;
    const indicatorLabel = submitBtn.querySelector('.indicator-label');
    const indicatorProgress = submitBtn.querySelector('.indicator-progress');

    submitBtn.disabled = true;
    indicatorLabel.style.display = 'none';
    indicatorProgress.style.display = 'inline';

    fetch('{{ route("batches.unassign") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ batch_ids: selectedBatchIds })
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        indicatorLabel.style.display = 'inline';
        indicatorProgress.style.display = 'none';

        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('assignBatchModal'));
                if (modal) modal.hide();
                if (data.reload) location.reload();
            }, 1000);
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        console.error('Error unassigning batches:', error);
        submitBtn.disabled = false;
        indicatorLabel.style.display = 'inline';
        indicatorProgress.style.display = 'none';
        toastr.error('{{ __("passwords.error_unassigning_batches") }}');
    });
});

// Reset form when modal is closed
document.getElementById('assignBatchModal')?.addEventListener('hidden.bs.modal', function() {
    document.getElementById('selectedCount').textContent = '0';
    selectedBatchIds = [];
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#assignLocationId, #assignDepartmentId').val('').trigger('change');
    }
});
</script>
@endpush