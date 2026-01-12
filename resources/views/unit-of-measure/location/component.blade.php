<div class="card-body py-4" id="locationIndexTable">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('auth._id')}}</th>
                    <th class="min-w-125px">{{__('pagination._location')}}</th>
                    <th class="min-w-125px">{{__('pagination._address')}}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th>
                    <th class="min-w-125px">{{__('auth._manager')}}</th>
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('pagination._primary')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="text-end min-w-100px">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($all_locations) && $all_locations->count() > 0)
                    @foreach ($all_locations as $location)
                        <tr data-role="{{ strtolower($location->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $location->id }}</div>
                            </td>
                            <td>{{ $location->name }}</td>
                            <td class="fw-bold text-warning ms-3">{{ $location->address ?? 'None' }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $location->locationCreater->name ?? 'None' }}</div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $location->locationManager->name ?? 'None' }}</div>
                            </td>
                            <td>{{ $location->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <select name="status" class="form-select form-select-solid form-select-sm" onchange="updatePrimaryStatus({{ $location->id }}, this.value)"
                                @cannot('update location') disabled @endcannot>
                                    <option value="1" {{ $location->is_primary == 1 ? 'selected' : '' }}><span>{{__('pagination._yes')}}</option>
                                    <option value="0" {{ $location->is_primary == 0 ? 'selected' : '' }}>{{__('pagination._no')}}</option>
                                </select>
                            </td>
                            <td>
                                <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateLocationStatus({{ $location->id }}, this.value)"
                                @cannot('update location') disabled @endcannot>
                                    <option value="1" {{ $location->is_active == 1 ? 'selected' : '' }}><span>{{__('auth._active')}}</option>
                                    <option value="0" {{ $location->is_active == 0 ? 'selected' : '' }}>{{__('auth._inactive')}}</option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit location')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                                data-bs-target="#editLocation{{$location->id}}">
                                                <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('Edit') }}</span>
                                        </button>
                                    @endcan
                                    @can('delete location')
                                        <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                                data-bs-target="#deleteLocationModal{{$location->id}}">
                                                <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('Delete') }}</span>
                                        </button>
                                    @endcan 
                                </div>

                                <!-- Delete User Modal -->
                                <div class="modal fade" id="deleteLocationModal{{$location->id}}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>{{ __('auth.are_you_sure') }}</p>
                                                <p>{{ __('auth.action_cannot') }}</p>
                                            </div>
                                            <div class="modal-footer">
                                                <!-- Discard Button -->
                                                <button type="button" id="closeDeleteModal{{$location->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$location->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('locations.destroy', $location->id) }}" 
                                                    data-item-id="{{ $location->id }}"
                                                    onclick="deleteItem(this)">
                                                    <span class="indicator-label">{{ __('auth._confirm') }}</span>
                                                    <span class="indicator-progress" style="display: none;">
                                                        {{__('auth.please_wait') }}
                                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @include('unit-of-measure.location.edit')
                                
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>




