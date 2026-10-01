<div class="modal fade" id="editCustomerGroup{{ $group->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('passwords.customer_group_edit') }} - {{ $group->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_customer_group_form{{ $group->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('auth._name') }}</span>
                                </label>
                                <input type="text" value="{{ $group->name }}"
                                       class="form-control form-control-solid" name="name" />
                                <div id="name{{ $group->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.discount_percentage') }}</span>
                                </label>
                                <div class="input-group input-group-solid">
                                    <input type="number"
                                           value="{{ $group->discount_percentage }}"
                                           class="form-control form-control-solid"
                                           name="discount_percentage"
                                           step="0.01" min="0" max="100" />
                                    <span class="input-group-text">%</span>
                                </div>
                                <div id="discount_percentage{{ $group->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.default') }}</span>
                                </label>
                                <div class="form-check form-switch form-check-custom form-check-solid mt-2">
                                    <input class="form-check-input" type="checkbox"
                                           name="is_default" value="1"
                                           id="is_default{{ $group->id }}"
                                           {{ $group->is_default ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_default{{ $group->id }}">
                                        {{ __('passwords.mark_as_default_group') }}
                                    </label>
                                </div>
                                <div id="is_default{{ $group->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="col-md-12 text-end">
                                <button type="button" id="closeModalEditButton{{ $group->id }}"
                                        class="btn btn-light me-3" data-bs-dismiss="modal">
                                    {{ __('auth._discard') }}
                                </button>
                                <button onclick="updateCustomerGroup({{ $group->id }})"
                                        id="editCustomerGroupButton{{ $group->id }}"
                                        type="button"
                                        class="btn btn-primary">
                                    <span class="indicator-label">{{ __('auth._update') }}</span>
                                    <span class="indicator-progress">
                                        {{ __('auth.please_wait') }}
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>