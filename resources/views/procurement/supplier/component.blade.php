@can('view supplier')
<div class="card-body py-4" id="reloadSupplierComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input"
                                type="checkbox"
                                data-kt-check="true"
                                data-kt-check-target="#kt_table_users .row-checkbox"
                                value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('pagination._id')}}</th>
                    <th class="min-w-125px">{{__('auth._name')}}</th> 
                    <th class="min-w-125px">{{__('passwords.contact_person')}}</th> 
                    <th class="min-w-125px">{{__('passwords._email')}}</th> 
                    <th class="min-w-125px">{{__('passwords._phone')}}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th> 
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($all_suppliers) && $all_suppliers->count() > 0)
                    @foreach ($all_suppliers as $supplier)
                        <tr data-role="{{ strtolower($supplier->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input row-checkbox" type="checkbox" value="{{ $supplier->id }}" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('payments._id')}}{{ $supplier->id }}</div>
                            </td>
                            
                            <td>{{ $supplier->name }}</td>

                            <td>
                                <div class="badge badge-light fw-bold">{{ $supplier->contact_person }}</div>
                            </td>

                            <td>
                                <div class="badge badge-light fw-bold">{{ $supplier->email }}</div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $supplier->phone }}</div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $supplier->supplierCreater->name ?? __('pagination._none')}}</div>
                            </td>
                            <td>{{ $supplier->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input type="checkbox"
                                        class="form-check-input status-switch"
                                        onchange="updateSupplierStatus({{ $supplier->id }}, this.checked ? 1 : 0)"
                                        {{ $supplier->is_active ? 'checked' : '' }}
                                        @cannot('update supplier') disabled @endcannot>
                                    <span id="supplier-label-{{ $supplier->id }}"
                                        class="form-check-label ms-2 fw-bold fs-6 text-gray-700">
                                        {{ $supplier->is_active ? __('auth._active') : __('auth._inactive') }}
                                    </span>
                                </label>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('view supplier')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-success d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewSupplier{{$supplier->id}}">
                                            <i class="bi bi-eye me-1 fs-5"></i> <span>{{ __('passwords._view') }}</span>
                                        </button>
                                    @endcan
                                    @can('edit supplier')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editSupplier{{$supplier->id}}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                        </button>
                                    @endcan
                                    @can('delete supplier')
                                        <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deletecategoryModal{{$supplier->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>

                                <!-- Delete User Modal -->
                                <div class="modal fade" id="deletecategoryModal{{$supplier->id}}" tabindex="-1" aria-hidden="true">
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
                                                <button type="button" id="closeDeleteModal{{$supplier->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$supplier->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('suppliers.destroy', $supplier->id) }}" 
                                                    data-item-id="{{ $supplier->id }}"
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
                                @include('procurement.supplier.edit')   
                                @include('procurement.supplier.view')                                
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endcan


