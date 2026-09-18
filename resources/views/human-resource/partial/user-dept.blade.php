<div class="modal fade delete-user-modal" id="editUserDeptModal{{ $employee->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('auth.user_department') }} — {{ $employee->first_name }}
                </h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>

            @unless(tenant_is_single_shop(auth()->user()->tenant_id))
                <div class="modal-body scroll-y mx-lg-5 my-7">
                    <form id="edit_user_form{{ $employee->id }}"
                          class="form"
                          action="{{ route('employees.updateDepartments', $employee->id) }}"
                          method="POST">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="user_id" value="{{ $employee->id }}">

                        @php
                            // Group departments under their parent location
                            $departmentsByLocation = $departments
                                ->groupBy('location_id');

                            $assignedDeptIds = optional($employee->departments)->pluck('id')->toArray() ?? [];
                            $assignedLocIds  = optional($employee->locations)->pluck('id')->toArray() ?? [];
                        @endphp

                        {{-- ═══════════════════════════════════════════════════════════ --}}
                        {{-- DEPARTMENT ASSIGNMENT — GROUPED BY LOCATION                 --}}
                        {{-- ═══════════════════════════════════════════════════════════ --}}
                        <div class="mb-8">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-diagram-3 me-1 text-primary"></i>
                                {{ __('auth._department') }}
                            </h6>

                            @forelse ($locations as $location)
                                @php
                                    $deptsInLocation = $departmentsByLocation->get($location->id, collect());
                                @endphp

                                @if ($deptsInLocation->count() > 0)
                                    <div class="mb-5">
                                        <!-- Location header -->
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge badge-light-info me-2">
                                                <i class="bi bi-geo-alt me-1"></i>
                                                {{ $location->name }}
                                            </span>

                                            <!-- Select all departments in this location -->
                                            <div class="form-check form-check-custom form-check-solid ms-auto">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       id="select-all-dept-loc-{{ $employee->id }}-{{ $location->id }}"
                                                       onchange="toggleGroupDepts({{ $employee->id }}, {{ $location->id }}, this.checked)">
                                                <label class="form-check-label fs-7"
                                                       for="select-all-dept-loc-{{ $employee->id }}-{{ $location->id }}">
                                                    {{ __('auth.select_all') }}
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Departments under this location -->
                                        <div class="row g-3 ps-3 border-start border-2 border-gray-200"
                                             id="dept-group-{{ $employee->id }}-{{ $location->id }}">
                                            @foreach ($deptsInLocation as $department)
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input type="checkbox"
                                                               class="form-check-input dept-checkbox-{{ $employee->id }}-{{ $location->id }}"
                                                               name="departments[]"
                                                               value="{{ $department->id }}"
                                                               id="dept-{{ $employee->id }}-{{ $department->id }}"
                                                               {{ in_array($department->id, $assignedDeptIds) ? 'checked' : '' }}>
                                                        <label class="form-check-label"
                                                               for="dept-{{ $employee->id }}-{{ $department->id }}">
                                                            {{ ucwords(str_replace('_', ' ', $department->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="alert alert-light text-muted">
                                    {{ __('auth.no_departments_available') }}
                                </div>
                            @endforelse

                            {{-- Departments with no parent location (defensive) --}}
                            @php
                                $orphaned = $departmentsByLocation->get(null, collect())
                                    ->merge($departmentsByLocation->get('', collect()));
                            @endphp
                            @if ($orphaned->count() > 0)
                                <div class="mb-5">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge badge-light-secondary me-2">
                                            <i class="bi bi-question-circle me-1"></i>
                                            {{ __('auth.unassigned') }}
                                        </span>
                                    </div>
                                    <div class="row g-3 ps-3 border-start border-2 border-gray-200">
                                        @foreach ($orphaned as $department)
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input type="checkbox"
                                                           class="form-check-input"
                                                           name="departments[]"
                                                           value="{{ $department->id }}"
                                                           id="dept-orphan-{{ $employee->id }}-{{ $department->id }}"
                                                           {{ in_array($department->id, $assignedDeptIds) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                           for="dept-orphan-{{ $employee->id }}-{{ $department->id }}">
                                                        {{ ucwords(str_replace('_', ' ', $department->name)) }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div id="departments{{ $employee->id }}"></div>
                        </div>

                        <div class="separator separator-dashed my-8"></div>

                        {{-- ═══════════════════════════════════════════════════════════ --}}
                        {{-- LOCATION ASSIGNMENT (flat, unchanged)                        --}}
                        {{-- ═══════════════════════════════════════════════════════════ --}}
                        <div class="mb-5">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-geo-alt me-1 text-info"></i>
                                {{ __('pagination._locations') }}
                            </h6>
                            <div class="row g-3">
                                @foreach ($locations as $location)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   class="form-check-input"
                                                   name="locations[]"
                                                   value="{{ $location->id }}"
                                                   id="loc-{{ $employee->id }}-{{ $location->id }}"
                                                   {{ in_array($location->id, $assignedLocIds) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="loc-{{ $employee->id }}-{{ $location->id }}">
                                                {{ ucwords(str_replace('_', ' ', $location->name)) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div id="locations{{ $employee->id }}"></div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="d-flex justify-content-center pt-10">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">
                                {{ __('auth._discard') }}
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">{{ __('auth._update') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>{{ __('auth.single_shop_plan') }}:</strong>
                    {{ __('auth.upgrade_for_multiple_shops') }}
                    <a href="/" class="btn btn-sm btn-outline-primary ms-2">
                        {{ __('auth.upgrade_plan') }}
                    </a>
                </div>
            @endunless
        </div>
    </div>
</div>


@push('scripts')
<script>
/**
 * Toggle all departments under a given location group.
 */
function toggleGroupDepts(employeeId, locationId, checked) {
    document
        .querySelectorAll(`.dept-checkbox-${employeeId}-${locationId}`)
        .forEach(cb => cb.checked = checked);
}
</script>
@endpush