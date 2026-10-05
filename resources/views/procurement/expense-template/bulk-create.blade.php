{{-- resources/views/procurement/expense-template/bulk-create.blade.php --}}
<div class="modal fade" id="kt_modal_bulk_templates" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-list-check me-2"></i>
                    {{ __('auth.bulk_add_templates') }}
                </h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary"
                        data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
            </div>

            <div class="modal-body scroll-y" style="max-height: 75vh;">
                <form id="bulkTemplatesForm">
                    @csrf

                    {{-- ═══════════════════════════════════════════════════ --}}
                    {{-- Names textarea                                      --}}
                    {{-- ═══════════════════════════════════════════════════ --}}
                    <div class="mb-7">
                        <label class="required fs-6 fw-semibold mb-2">
                            {{ __('auth.expense_names') }}
                        </label>
                        <textarea
                            name="names_raw"
                            id="bulkNamesRaw"
                            class="form-control form-control-solid"
                            rows="10"
                            placeholder="{{ __('auth.bulk_names_placeholder') }}"
                        ></textarea>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                {{ __('auth.bulk_names_hint') }}
                            </small>
                            <span class="badge badge-light-primary" id="bulkNameCount">
                                0 {{ __('auth.names_detected') }}
                            </span>
                        </div>
                        <div class="invalid-feedback d-block" id="names_raw_error"></div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════ --}}
                    {{-- Shared defaults                                     --}}
                    {{-- ═══════════════════════════════════════════════════ --}}
                    <div class="separator separator-dashed my-5">
                        <span class="text-muted fw-semibold fs-7 px-3 bg-white">
                            {{ __('auth.shared_defaults') }}
                        </span>
                    </div>

                    <div class="row g-5 mb-5">
                        {{-- Category (required) --}}
                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">
                                {{ __('pagination.category') }}
                            </label>
                            <select name="category_id" class="form-select form-select-solid"
                                    data-control="select2"
                                    data-dropdown-parent="#kt_modal_bulk_templates">
                                <option value="">{{ __('auth.select_category') }}</option>
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat->id }}">
                                        {{ $cat->name }}@if($cat->code) ({{ $cat->code }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-block" id="category_id_error"></div>
                        </div>

                        {{-- Supplier --}}
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">
                                {{ __('auth.supplier') }}
                            </label>
                            <select name="supplier_id" class="form-select form-select-solid"
                                    data-control="select2"
                                    data-allow-clear="true"
                                    data-dropdown-parent="#kt_modal_bulk_templates">
                                <option value="">{{ __('auth.none') }}</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-5 mb-5">
                        {{-- Location (single) --}}
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">
                                {{ __('auth.default_location') }}
                            </label>
                            <select name="location_id" id="bulkLocationId"
                                    class="form-select form-select-solid"
                                    data-control="select2"
                                    data-allow-clear="true"
                                    data-dropdown-parent="#kt_modal_bulk_templates">
                                <option value="">{{ __('auth.none') }}</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted fs-8">
                                {{ __('auth.default_location_hint') }}
                            </small>
                        </div>

                        {{-- Department (populated after picking a location) --}}
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">
                                {{ __('auth.default_department') }}
                            </label>
                            <select name="department_id" id="bulkDepartmentId"
                                    class="form-select form-select-solid"
                                    data-control="select2"
                                    data-allow-clear="true"
                                    data-dropdown-parent="#kt_modal_bulk_templates"
                                    data-placeholder="{{ __('auth.select_department') }}">
                                <option value="">{{ __('auth.none') }}</option>
                                {{-- Options are loaded via AJAX when a location is picked --}}
                            </select>
                            <small class="text-muted fs-8" id="bulkDepartmentHint">
                                {{ __('auth.default_department_hint') }}
                            </small>
                        </div>
                    </div>

                    <div class="row g-5 mb-5">
                        {{-- Frequency --}}
                        <div class="col-md-4">
                            <label class="fs-6 fw-semibold mb-2">
                                {{ __('pagination.recurring_frequency') }}
                            </label>
                            <select name="frequency" class="form-select form-select-solid">
                                <option value="random" selected>{{ __('auth.frequency_random') }}</option>
                                <option value="daily">{{ __('auth.frequency_daily') }}</option>
                                <option value="weekly">{{ __('auth.frequency_weekly') }}</option>
                                <option value="biweekly">{{ __('auth.frequency_biweekly') }}</option>
                                <option value="monthly">{{ __('auth.frequency_monthly') }}</option>
                                <option value="quarterly">{{ __('auth.frequency_quarterly') }}</option>
                                <option value="annually">{{ __('auth.frequency_annually') }}</option>
                                <option value="once">{{ __('auth.frequency_once') }}</option>
                            </select>
                        </div>

                        {{-- Default amount --}}
                        <div class="col-md-4">
                            <label class="fs-6 fw-semibold mb-2">
                                {{ __('auth.default_amount_optional') }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="default_amount"
                                       class="form-control form-control-solid"
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>

                        {{-- Flags --}}
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="d-flex flex-column gap-2">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox"
                                           name="requires_receipt" value="1" checked>
                                    <span class="form-check-label fs-7">
                                        {{ __('auth.requires_receipt') }}
                                    </span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox"
                                           name="requires_approval" value="1">
                                    <span class="form-check-label fs-7">
                                        {{ __('auth.requires_approval') }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('auth._cancel') }}
                </button>
                <button type="button"
                        id="bulkTemplatesSubmit"
                        class="btn btn-primary"
                        onclick="submitBulkTemplates()">
                    <span class="indicator-label">
                        <i class="bi bi-plus-circle me-1"></i>
                        {{ __('auth.create_templates') }}
                    </span>
                    <span class="indicator-progress" style="display:none;">
                        {{ __('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>