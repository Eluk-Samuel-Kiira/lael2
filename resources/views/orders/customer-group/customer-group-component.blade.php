@can('view customer-group')
<div class="card-body py-4" id="reloadCustomerGroupComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_customer_group_table .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{ __('passwords.group_id') }}</th>
                    <th class="min-w-200px">{{ __('auth._name') }}</th>
                    <th class="min-w-150px text-center">{{ __('passwords.discount_percentage') }}</th>
                    <th class="min-w-150px text-center">{{ __('passwords.customers_count') }}</th>
                    <th class="min-w-100px text-center">{{ __('passwords.default') }}</th>
                    <th class="min-w-125px">{{ __('auth.created_at') }}</th>
                    <th class="min-w-100px text-end">{{ __('auth._actions') }}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold" id="kt_customer_group_table">
                @if (!empty($customerGroups) && $customerGroups->count() > 0)
                    @foreach ($customerGroups as $group)
                        <tr data-role="{{ strtolower($group->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="{{ $group->id }}" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ __('payments._id') }}{{ $group->id }}</div>
                            </td>
                            <td>
                                <span class="fw-bold text-gray-800">{{ $group->name }}</span>
                                <div class="text-muted fs-7">
                                    {{ __('passwords.created_by') }}: {{ $group->customerGroupCreater->name ?? '—' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light-info fw-bold">
                                    {{ number_format($group->discount_percentage, 2) }}%
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light-primary">
                                    {{ $group->customers_count }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($group->is_default)
                                    <span class="badge badge-light-success">
                                        <i class="ki-duotone ki-check fs-4 me-1"></i>
                                        {{ __('passwords.default') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $group->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit customer-group')
                                        <button class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editCustomerGroup{{ $group->id }}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i>
                                            <span>{{ __('auth._edit') }}</span>
                                        </button>
                                    @endcan

                                    @can('delete customer-group')
                                        <button type="button"
                                                class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCustomerGroupModal{{ $group->id }}">
                                            <i class="bi bi-trash me-1 fs-5"></i>
                                            <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>

                                {{-- Delete modal --}}
                                <div class="modal fade" id="deleteCustomerGroupModal{{ $group->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>{{ __('auth.are_you_sure') }}</p>
                                                <p>{{ __('auth.action_cannot') }}</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">
                                                    {{ __('auth._discard') }}
                                                </button>
                                                <button type="button"
                                                        class="btn btn-danger"
                                                        data-item-url="{{ route('customer-group.destroy', $group->id) }}"
                                                        data-item-id="{{ $group->id }}"
                                                        onclick="deleteItem(this)">
                                                    <span class="indicator-label">{{ __('auth._confirm') }}</span>
                                                    <span class="indicator-progress" style="display: none;">
                                                        {{ __('auth.please_wait') }}
                                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @include('orders.customer-group.edit-customer-group')
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <x-liveblade-pagination
        :paginator="$customerGroups"
        id="customerGroupPagination"
        route="{{ route('customer-group.index') }}"
        search-input-id="customerGroupSearchInput"
        :show-info="true"
        :show-per-page="true"
        :per-page-options="[15, 25, 50, 100]"
        data-lb-component="reloadCustomerGroupComponent"
    />
</div>
@endcan