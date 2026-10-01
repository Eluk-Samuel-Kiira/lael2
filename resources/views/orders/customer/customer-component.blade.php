@can('view customer')
<div class="card-body py-4" id="reloadCustomerComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_customer_table .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{ __('passwords.customer_id') }}</th>
                    <th class="min-w-200px">{{ __('passwords.customer_name') }}</th>
                    <th class="min-w-200px">{{ __('auth._contact') }}</th>
                    <th class="min-w-150px">{{ __('passwords.customer_group') }}</th>
                    <th class="min-w-150px">{{ __('passwords.location') }}</th>
                    <th class="min-w-100px">{{ __('auth._status') }}</th>
                    <th class="min-w-100px text-end">{{ __('auth._actions') }}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold" id="kt_customer_table">
                @if (!empty($customers) && $customers->count() > 0)
                    @foreach ($customers as $customer)
                        <tr data-role="{{ strtolower($customer->first_name . ' ' . $customer->last_name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="{{ $customer->id }}" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ __('payments._id') }}{{ $customer->id }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px symbol-circle me-3">
                                        <div class="symbol-label bg-light-primary">
                                            <span class="fw-bold text-primary">
                                                {{ strtoupper(substr($customer->first_name ?? 'C', 0, 1)) }}
                                                {{ strtoupper(substr($customer->last_name ?? '', 0, 1)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-gray-800">
                                            {{ $customer->first_name }} {{ $customer->last_name }}
                                        </span>
                                        @if($customer->tax_number)
                                            <small class="text-muted">
                                                {{ __('passwords._tax') }}: {{ $customer->tax_number }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    @if($customer->email)
                                        <span><i class="ki-duotone ki-sms fs-6 me-1"></i>{{ $customer->email }}</span>
                                    @endif
                                    @if($customer->phone)
                                        <span><i class="ki-duotone ki-phone fs-6 me-1"></i>{{ $customer->phone }}</span>
                                    @endif
                                    @if($customer->city || $customer->country_code)
                                        <small class="text-muted">
                                            {{ $customer->city }}{{ $customer->city && $customer->country_code ? ', ' : '' }}{{ $customer->country_code }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($customer->group)
                                    <span class="badge badge-light-info fw-bold">
                                        {{ $customer->group->name }}
                                        @if($customer->group->discount_percentage > 0)
                                            &middot; {{ number_format($customer->group->discount_percentage, 2) }}%
                                        @endif
                                    </span>
                                @else
                                    <span class="badge badge-light-secondary">{{ __('pagination._none') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-light">
                                    {{ $customer->customerCreater->name ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <select name="status"
                                        class="form-select form-select-solid form-select-sm"
                                        onchange="updateCustomerStatus({{ $customer->id }}, this.value)"
                                        @cannot('edit customer') disabled @endcannot>
                                    <option value="1" {{ $customer->is_active == 1 ? 'selected' : '' }}>
                                        {{ __('payments.active') }}
                                    </option>
                                    <option value="0" {{ $customer->is_active == 0 ? 'selected' : '' }}>
                                        {{ __('payments.inactive') }}
                                    </option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit customer')
                                        <button class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editCustomer{{ $customer->id }}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i>
                                            <span>{{ __('auth._edit') }}</span>
                                        </button>
                                    @endcan

                                    @can('delete customer')
                                        <button type="button"
                                                class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCustomerModal{{ $customer->id }}">
                                            <i class="bi bi-trash me-1 fs-5"></i>
                                            <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>

                                {{-- Delete modal --}}
                                <div class="modal fade" id="deleteCustomerModal{{ $customer->id }}" tabindex="-1" aria-hidden="true">
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
                                                        data-item-url="{{ route('customer.destroy', $customer->id) }}"
                                                        data-item-id="{{ $customer->id }}"
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

                                @include('orders.customer.edit-customer')
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <x-liveblade-pagination
        :paginator="$customers"
        id="customerPagination"
        route="{{ route('customer.index') }}"
        search-input-id="customerSearchInput"
        :show-info="true"
        :show-per-page="true"
        :per-page-options="[15, 25, 50, 100]"
        data-lb-component="reloadCustomerComponent"
    />
</div>
@endcan