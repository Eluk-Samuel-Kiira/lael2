@can('view uom')
<div class="card-body py-4" id="uomIndexTable">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('auth._uom_id')}}</th>
                    <th class="min-w-125px">{{__('auth._uom')}}</th>
                    <th class="min-w-125px">{{__('auth._symbol')}}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th>  
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($all_uoms) && $all_uoms->count() > 0)
                    @foreach ($all_uoms as $uom)
                        <tr data-role="{{ strtolower($uom->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('payments._id')}}{{ $uom->id }}</div>
                            </td>
                            <td>{{ $uom->name }}</td>
                            <td class="fw-bold text-primary ms-3">{{ $uom->symbol }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $uom->UOMCreater->name ?? __('payments.none') }}</div>
                            </td>
                            <td>{{ $uom->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateStatusUOM({{ $uom->id }}, this.value)"
                                @cannot('update uom') disabled @endcannot>
                                    <option value="1" {{ $uom->isActive == 1 ? 'selected' : '' }}><span>{{__('payments.active')}}</option>
                                    <option value="0" {{ $uom->isActive == 0 ? 'selected' : '' }}>{{__('payments.inactive')}}</option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit uom')
                                        <button 
                                        class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                        data-bs-toggle="modal" 
                                            data-bs-target="#editDepartment{{$uom->id}}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                            </button>
                                    @endcan
                                    @can('delete uom')
                                        <button type="button" 
                                        class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                        data-bs-toggle="modal" 
                                            data-bs-target="#deleteUOMModal{{$uom->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>

                                <!-- Delete User Modal -->
                                <div class="modal fade" id="deleteUOMModal{{$uom->id}}" tabindex="-1" aria-hidden="true">
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
                                                <button type="button" id="closeDeleteModal{{$uom->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$uom->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('uom.destroy', $uom->id) }}" 
                                                    data-item-id="{{ $uom->id }}"
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
                                @include('unit-of-measure.partials.edit-uom')
                                
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endcan

