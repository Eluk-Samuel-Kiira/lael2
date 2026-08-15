<!-- Serial Number Management Modal -->
<div class="modal fade" id="serialManagementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-upc-scan me-2 text-primary"></i>
                    {{ __('pagination.serial_numbers') }}
                    <span id="serialVariantName" class="fs-6 text-muted ms-2"></span>
                </h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body px-5 my-7">
                <!-- Summary Cards -->
                <div class="row g-4 mb-6" id="serialSummary">
                    <div class="col-md-2">
                        <div class="card bg-light-primary">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-primary" id="totalSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.total') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-light-success">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-success" id="availableSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.available') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-light-danger">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-danger" id="soldSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.sold') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-light-warning">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-warning" id="reservedSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.reserved') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-light-info">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-info" id="returnedSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.returned') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-light-secondary">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold text-secondary" id="damagedSerials">0</div>
                                <div class="text-muted fs-7">{{ __('passwords.damaged') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate/Import/Actions -->
                <div class="row g-3 mb-6">
                    <div class="col-md-4">
                        <div class="card card-dashed p-3">
                            <h6 class="fw-bold mb-2">{{ __('passwords.generate_serials') }}</h6>
                            <div class="d-flex gap-2">
                                <input type="number" id="generateSerialQuantity" class="form-control form-control-sm" 
                                    placeholder="{{ __('passwords.quantity') }}" min="1" max="1000" value="1">
                                <input type="text" id="generateSerialPrefix" class="form-control form-control-sm" 
                                    placeholder="{{ __('passwords.prefix') }}" maxlength="10" style="max-width: 120px;">
                                <button type="button" class="btn btn-sm btn-primary" onclick="generateSerials()">
                                    <i class="bi bi-plus-circle me-1"></i> {{ __('passwords.generate') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-dashed p-3">
                            <h6 class="fw-bold mb-2">{{ __('passwords.import_serials') }}</h6>
                            <div class="d-flex gap-2">
                                <textarea id="importSerialInput" class="form-control form-control-sm" 
                                    placeholder="{{ __('passwords.enter_serials_one_per_line') }}" 
                                    rows="2" style="resize: vertical; min-height: 50px;"></textarea>
                                <button type="button" class="btn btn-sm btn-success" onclick="importSerials()" style="align-self: flex-end;">
                                    <i class="bi bi-upload me-1"></i> {{ __('passwords.import') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-dashed p-3">
                            <h6 class="fw-bold mb-2">{{ __('passwords.assign_serials') }}</h6>
                            <div class="row g-2">
                                <div class="col-5">
                                    <select id="assignLocationId" class="form-select form-select-sm">
                                        <option value="">{{ __('passwords.select_location') }}</option>
                                        @foreach($locations ?? [] as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-5">
                                    <select id="assignDepartmentId" class="form-select form-select-sm">
                                        <option value="">{{ __('passwords.select_department') }}</option>
                                        @foreach($departments ?? [] as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-sm btn-warning w-100" onclick="assignSelectedSerials()">
                                        <i class="bi bi-tags me-1"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">{{ __('passwords.select_serials_and_assign') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Select All Row -->
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="selectAllSerials" onclick="toggleAllSerials()">
                            <label class="form-check-label" for="selectAllSerials">
                                {{ __('passwords.select_all') }}
                            </label>
                        </div>
                        <span class="badge badge-light-primary" id="selectedSerialsCount">0</span>
                        <span class="text-muted">{{ __('passwords.selected') }}</span>
                    </div>
                </div>

                <!-- Serial Numbers Table -->
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fw-bold fs-7 text-gray-500 border-bottom-0">
                                <th class="min-w-50px">#</th>
                                <th class="min-w-150px">{{ __('passwords.serial_number') }}</th>
                                <th class="min-w-100px">{{ __('passwords.status') }}</th>
                                <th class="min-w-150px">{{ __('passwords.location') }}</th>
                                <th class="min-w-150px">{{ __('passwords.department') }}</th>
                                <th class="min-w-150px">{{ __('passwords.order') }}</th>
                                <th class="min-w-150px">{{ __('passwords.created_at') }}</th>
                                <th class="min-w-100px text-end">{{ __('auth._actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="serialTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    {{ __('passwords.no_serials_found') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                <button type="button" class="btn btn-primary" onclick="refreshSerials()">
                    <i class="bi bi-arrow-clockwise me-1"></i> {{ __('passwords.refresh') }}
                </button>
            </div>
        </div>
    </div>
</div>








<script>
    let currentVariantId = null;

    function openSerialManagementModal(variantId, variantName) {
        currentVariantId = variantId;
        document.getElementById('serialVariantName').textContent = '- ' + variantName;
        document.getElementById('serialTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">{{ __('passwords.loading_serials') }}</p>
                </td>
            </tr>
        `;
        
        const modal = new bootstrap.Modal(document.getElementById('serialManagementModal'));
        modal.show();
        refreshSerials();
    }

    function refreshSerials() {
        if (!currentVariantId) return;
        
        fetch(`/serials/variant/${currentVariantId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSerials(data.data);
            } else {
                toastr.error(data.message || '{{ __("passwords.error_loading_serials") }}');
            }
        })
        .catch(error => {
            console.error('Error loading serials:', error);
            toastr.error('{{ __("passwords.error_loading_serials") }}');
        });
    }

    function renderSerials(data) {
        const summary = data.summary || {};
        const serials = data.serials || [];
        
        // ✅ DEBUG: Log the serials data to see what's coming
        // console.log('Serials data:', serials);
        // console.log('First serial:', serials.length > 0 ? serials[0] : 'No serials');
        
        document.getElementById('totalSerials').textContent = summary.total || 0;
        document.getElementById('availableSerials').textContent = summary.available || 0;
        document.getElementById('soldSerials').textContent = summary.sold || 0;
        document.getElementById('reservedSerials').textContent = summary.reserved || 0;
        document.getElementById('returnedSerials').textContent = summary.returned || 0;
        document.getElementById('damagedSerials').textContent = summary.damaged || 0;
        
        const tbody = document.getElementById('serialTableBody');
        
        if (serials.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        {{ __('passwords.no_serials_found') }}
                    </td>
                </tr>
            `;
            return;
        }
        
        const statusColors = {
            'available': 'success',
            'sold': 'danger',
            'reserved': 'warning',
            'returned': 'info',
            'lost': 'secondary',
            'damaged': 'dark'
        };
        
        tbody.innerHTML = serials.map((serial, index) => {
            // ✅ DEBUG: Log each serial
            // console.log(`Serial ${index}:`, {
            //     id: serial.id,
            //     serial_number: serial.serial_number,
            //     location_name: serial.location_name,
            //     department_name: serial.department_name,
            //     location: serial.location,
            //     department: serial.department,
            //     status: serial.status
            // });
            
            // ✅ Get location and department names from multiple possible sources
            const locationName = serial.location_name || 
                                (serial.location ? serial.location.name : null) || 
                                'N/A';
            
            const departmentName = serial.department_name || 
                                (serial.department ? serial.department.name : null) || 
                                'N/A';
            
            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <span class="fw-bold">${serial.serial_number}</span>
                        ${serial.notes ? `<br><small class="text-muted">${serial.notes.substring(0, 30)}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge badge-light-${statusColors[serial.status] || 'secondary'}">
                            ${serial.status_label || serial.status}
                        </span>
                    </td>
                    <td>
                        ${locationName !== 'N/A' ? 
                            `<span class="badge badge-light-info">${locationName}</span>` : 
                            '<span class="text-muted">N/A</span>'}
                    </td>
                    <td>
                        ${departmentName !== 'N/A' ? 
                            `<span class="badge badge-light-primary">${departmentName}</span>` : 
                            '<span class="text-muted">N/A</span>'}
                    </td>
                    <td>${serial.order_id ? '#' + serial.order_id : 'N/A'}</td>
                    <td>${new Date(serial.created_at).toLocaleDateString()}</td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            ${serial.status === 'available' ? `
                                <input type="checkbox" class="form-check-input serial-checkbox" 
                                    value="${serial.id}" style="margin-right: 5px;">
                                <button class="btn btn-sm btn-icon btn-light-primary" 
                                    onclick="updateSerialStatus(${serial.id}, 'sold')" 
                                    title="{{ __('passwords.mark_sold') }}">
                                    <i class="bi bi-cart-check fs-5"></i>
                                </button>
                                <button class="btn btn-sm btn-icon btn-light-warning" 
                                    onclick="updateSerialStatus(${serial.id}, 'reserved')" 
                                    title="{{ __('passwords.mark_reserved') }}">
                                    <i class="bi bi-bookmark fs-5"></i>
                                </button>
                            ` : ''}
                            ${serial.status === 'sold' ? `
                                <button class="btn btn-sm btn-icon btn-light-info" 
                                    onclick="updateSerialStatus(${serial.id}, 'returned')" 
                                    title="{{ __('passwords.mark_returned') }}">
                                    <i class="bi bi-arrow-counterclockwise fs-5"></i>
                                </button>
                            ` : ''}
                            ${serial.status !== 'sold' ? `
                                <button class="btn btn-sm btn-icon btn-light-danger" 
                                    onclick="deleteSerial(${serial.id})" 
                                    title="{{ __('passwords.delete') }}">
                                    <i class="bi bi-trash fs-5"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        
        // Reset selected count
        document.getElementById('selectedSerialsCount').textContent = '0';
    }

    function getSelectedSerials() {
        const checkboxes = document.querySelectorAll('.serial-checkbox:checked');
        return Array.from(checkboxes).map(cb => parseInt(cb.value));
    }

    function assignSelectedSerials() {
        if (!currentVariantId) return;
        
        const selectedIds = getSelectedSerials();
        
        if (selectedIds.length === 0) {
            toastr.warning('{{ __("passwords.select_serials_to_assign") }}');
            return;
        }
        
        const locationId = document.getElementById('assignLocationId').value;
        const departmentId = document.getElementById('assignDepartmentId').value;
        
        if (!locationId && !departmentId) {
            toastr.warning('{{ __("passwords.select_location_or_department") }}');
            return;
        }
        
        if (!confirm(`{{ __("passwords.confirm_assign_selected_serials") }} (${selectedIds.length})`)) {
            return;
        }
        
        fetch('/serials/assign-selected', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                serial_ids: selectedIds,
                location_id: locationId || null,
                department_id: departmentId || null
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                refreshSerials();
                document.getElementById('assignLocationId').value = '';
                document.getElementById('assignDepartmentId').value = '';
                document.querySelectorAll('.serial-checkbox').forEach(cb => cb.checked = false);
                document.getElementById('selectedSerialsCount').textContent = '0';
                if (document.getElementById('selectAllSerials')) {
                    document.getElementById('selectAllSerials').checked = false;
                }
            } else {
                toastr.error(data.message);
            }
        })
        .catch(error => {
            console.error('Error assigning serials:', error);
            toastr.error('{{ __("passwords.error_assigning_serials") }}');
        });
    }

    function updateSelectedCount() {
        const selected = document.querySelectorAll('.serial-checkbox:checked');
        document.getElementById('selectedSerialsCount').textContent = selected.length;
    }

    function toggleAllSerials() {
        const selectAll = document.getElementById('selectAllSerials');
        if (!selectAll) return;
        const checkboxes = document.querySelectorAll('.serial-checkbox');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateSelectedCount();
    }

    // Event listeners for checkbox count
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('serial-checkbox')) {
            updateSelectedCount();
        }
    });

    function generateSerials() {
        if (!currentVariantId) return;
        
        const quantity = parseInt(document.getElementById('generateSerialQuantity').value) || 1;
        const prefix = document.getElementById('generateSerialPrefix').value.trim();
        
        if (quantity < 1 || quantity > 1000) {
            toastr.warning('{{ __("passwords.enter_valid_quantity") }}');
            return;
        }
        
        fetch('/serials/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                variant_id: currentVariantId,
                quantity: quantity,
                prefix: prefix
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                refreshSerials();
                document.getElementById('generateSerialQuantity').value = 1;
                document.getElementById('generateSerialPrefix').value = '';
            } else {
                toastr.error(data.message);
            }
        })
        .catch(error => {
            console.error('Error generating serials:', error);
            toastr.error('{{ __("passwords.error_generating_serials") }}');
        });
    }

    function importSerials() {
        if (!currentVariantId) return;
        
        const input = document.getElementById('importSerialInput');
        const serialNumbers = input.value.trim();
        
        if (!serialNumbers) {
            toastr.warning('{{ __("passwords.enter_serial_numbers") }}');
            return;
        }
        
        fetch('/serials/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                variant_id: currentVariantId,
                serial_numbers: serialNumbers
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                refreshSerials();
                input.value = '';
            } else {
                toastr.error(data.message);
            }
        })
        .catch(error => {
            console.error('Error importing serials:', error);
            toastr.error('{{ __("passwords.error_importing_serials") }}');
        });
    }

    function updateSerialStatus(serialId, status) {
        const statusLabels = {
            'sold': '{{ __("passwords.sold") }}',
            'reserved': '{{ __("passwords.reserved") }}',
            'returned': '{{ __("passwords.returned") }}',
            'available': '{{ __("passwords.available") }}'
        };
        
        if (!confirm(`{{ __("passwords.confirm_change_status") }} ${statusLabels[status] || status}?`)) {
            return;
        }
        
        fetch(`/serials/${serialId}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                refreshSerials();
            } else {
                toastr.error(data.message);
            }
        })
        .catch(error => {
            console.error('Error updating serial status:', error);
            toastr.error('{{ __("passwords.error_updating_serial") }}');
        });
    }

    function deleteSerial(serialId) {
        if (!confirm('{{ __("passwords.confirm_delete_serial") }}')) {
            return;
        }
        
        fetch(`/serials/${serialId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                refreshSerials();
            } else {
                toastr.error(data.message);
            }
        })
        .catch(error => {
            console.error('Error deleting serial:', error);
            toastr.error('{{ __("passwords.error_deleting_serial") }}');
        });
    }
</script>