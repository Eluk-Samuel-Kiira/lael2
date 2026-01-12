<div class="card-body py-4" id="reloadPromotionComponent">
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
                    <th class="min-w-125px">{{__('pagination.discount_type')}}</th> 
                    <th class="min-w-125px">{{__('pagination.discount_value')}}</th> 
                    <th class="min-w-125px">{{__('pagination.start_date')}}</th>
                    <th class="min-w-125px">{{__('pagination.end_date')}}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th> 
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($all_promotions) && $all_promotions->count() > 0)
                    @foreach ($all_promotions as $promotion)
                        <tr data-role="{{ strtolower($promotion->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input row-checkbox" type="checkbox" value="{{ $promotion->id }}" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $promotion->id }}</div>
                            </td>
                            
                            <td>{{ $promotion->name }}</td>

                            <td>
                                <div class="badge badge-light fw-bold">{{ ucwords(str_replace('_', ' ', $promotion->discount_type)) }}</div>
                            </td>

                            <td>
                                <div class="badge badge-light fw-bold">{{ $promotion->discount_value }}</div>
                            </td>
                            <td>{{ $promotion->start_date->format('d M Y, h:i a') }}</td>
                            <td>{{ $promotion->end_date->format('d M Y, h:i a') }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $promotion->Promotioncreator->name ?? __('pagination._none')}}</div>
                            </td>
                            <td>{{ $promotion->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input type="checkbox"
                                        class="form-check-input status-switch"
                                        onchange="updatePromotionStatus({{ $promotion->id }}, this.checked ? 1 : 0)"
                                        {{ $promotion->is_active ? 'checked' : '' }}
                                        @cannot('update promotion') disabled @endcannot>

                                    <span id="promotion-label-{{ $promotion->id }}"
                                        class="form-check-label ms-2 fw-bold fs-6 text-gray-700">
                                        {{ $promotion->is_active ? __('auth._active') : __('auth._inactive') }}
                                    </span>
                                </label>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit promotion')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editTax{{$promotion->id}}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                        </button>
                                    @endcan
                                    @can('delete promotion')
                                        <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deletecategoryModal{{$promotion->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>

                                <!-- Delete User Modal -->
                                <div class="modal fade" id="deletecategoryModal{{$promotion->id}}" tabindex="-1" aria-hidden="true">
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
                                                <button type="button" id="closeDeleteModal{{$promotion->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$promotion->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('promotion.destroy', $promotion->id) }}" 
                                                    data-item-id="{{ $promotion->id }}"
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
                                @include('promotion.promotion.edit')
                                
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>


