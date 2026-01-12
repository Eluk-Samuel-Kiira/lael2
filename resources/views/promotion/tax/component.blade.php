<div class="card-body py-4" id="reloadTaxComponent">
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
                    <th class="min-w-125px">{{__('pagination._code')}}</th> 
                    <th class="min-w-125px">{{__('pagination._rate')}}</th> 
                    <th class="min-w-125px">{{__('pagination._type')}}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th> 
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($all_taxes) && $all_taxes->count() > 0)
                    @foreach ($all_taxes as $tax)
                        <tr data-role="{{ strtolower($tax->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input row-checkbox" type="checkbox" value="{{ $tax->id }}" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $tax->id }}</div>
                            </td>
                            
                            <td>{{ $tax->name }}</td>

                            <td>
                                <div class="badge badge-light fw-bold">{{ $tax->code }}</div>
                            </td>

                            <td>
                                <div class="badge badge-light fw-bold">{{ $tax->rate }}</div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $tax->type }}</div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $tax->taxCreater->name ?? __('pagination._none')}}</div>
                            </td>
                            <td>{{ $tax->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input type="checkbox"
                                        class="form-check-input status-switch"
                                        onchange="updateTaxesStatus({{ $tax->id }}, this.checked ? 1 : 0)"
                                        {{ $tax->is_active ? 'checked' : '' }}
                                        @cannot('update tax') disabled @endcannot>
                                    <span id="tax-label-{{ $tax->id }}"
                                        class="form-check-label ms-2 fw-bold fs-6 text-gray-700">
                                        {{ $tax->is_active ? __('auth._active') : __('auth._inactive') }}
                                    </span>
                                </label>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit tax')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editTax{{$tax->id}}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                        </button>
                                    @endcan
                                    @can('delete tax')
                                        <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deletecategoryModal{{$tax->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>

                                <!-- Delete User Modal -->
                                <div class="modal fade" id="deletecategoryModal{{$tax->id}}" tabindex="-1" aria-hidden="true">
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
                                                <button type="button" id="closeDeleteModal{{$tax->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$tax->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('tax.destroy', $tax->id) }}" 
                                                    data-item-id="{{ $tax->id }}"
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
                                @include('promotion.tax.edit')
                                
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>


