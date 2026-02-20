<div class="card-body py-4" id="currencyIndexTable">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('auth.currecy_id')}}</th>
                    <th class="min-w-125px">{{__('auth._code')}}</th>
                    <th class="min-w-125px">{{__('auth._name')}}</th>
                    <th class="min-w-125px">{{__('auth._symbol')}}</th>
                    <th class="min-w-125px">{{__('auth._exchange_rate')}}{{ tenant_currency() }}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th>
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($all_currencies) && $all_currencies->count() > 0)
                    @foreach ($all_currencies as $currency)
                        <tr data-role="{{ strtolower($currency->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $currency->id }}</div>
                            </td>
                            <td>
                                {{ $currency->code }}
                                @if($currency->is_base_currency)
                                    <span class="badge badge-success badge-sm ms-1">Default</span>
                                @endif
                            </td>
                            <td>{{ $currency->name }}</td>
                            <td>
                                <div class="fw-bold text-primary ms-3">{{ $currency->symbol }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-success ms-3">{{ $currency->exchange_rate }}</div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $currency->currencyCreater->name ?? 'None' }}</div>
                            </td>
                            <td>{{ $currency->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateStatusCurrency({{ $currency->id }}, this.value)" @if($currency->is_base_currency) disabled @endif>
                                    <option value="1" {{ $currency->is_active == 1 ? 'selected' : '' }}><span>{{__('auth._active')}}</option>
                                    <option value="0" {{ $currency->is_active == 0 ? 'selected' : '' }}>{{__('auth._inactive')}}</option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @if(!$currency->is_base_currency)
                                    <!-- Edit User Button -->
                                    <button 
                                        class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editCurrency{{$currency->id}}">
                                        <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                    </button>
                                    <!-- Delete User Button -->
                                    <button type="button" 
                                        class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                        data-bs-toggle="modal" 
                                            data-bs-target="#deleteUserModal{{$currency->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                    </button>
                                    @endif
                                </div>

                                <!-- Delete User Modal -->
                                <div class="modal fade" id="deleteUserModal{{$currency->id}}" tabindex="-1" aria-hidden="true">
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
                                                <button type="button" id="closeDeleteModal{{$currency->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$currency->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('currency.destroy', $currency->id) }}" 
                                                    data-item-id="{{ $currency->id }}"
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
                                @include('settings.currency.edit-currency')
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
