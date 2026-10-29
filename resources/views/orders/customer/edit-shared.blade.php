<div class="modal fade" id="customerEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('passwords.customer_edit') }} —
                    <span id="customerEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="customerEditForm" class="form">
                    @csrf
                    <input type="hidden" id="customerEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">First Name</label>
                            <input type="text" name="first_name" class="form-control form-control-solid" maxlength="100">
                            <div id="first_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Last Name</label>
                            <input type="text" name="last_name" class="form-control form-control-solid" maxlength="100">
                            <div id="last_name"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control form-control-solid" maxlength="255">
                            <div id="email"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Phone</label>
                            <input type="text" name="phone" class="form-control form-control-solid" maxlength="50">
                            <div id="phone"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Customer Group</label>
                            <select name="group_id" class="form-select form-select-solid">
                                <option value="">— None —</option>
                                @foreach(\App\Models\CustomerGroup::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name']) as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                            <div id="group_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax Number</label>
                            <input type="text" name="tax_number" class="form-control form-control-solid" maxlength="50">
                            <div id="tax_number"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Birth Date</label>
                            <input type="date" name="birth_date" class="form-control form-control-solid">
                            <div id="birth_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country Code</label>
                            <input type="text" name="country_code" class="form-control form-control-solid" maxlength="2">
                            <div id="country_code"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control form-control-solid" rows="2"></textarea>
                            <div id="address"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control form-control-solid" maxlength="100">
                            <div id="city"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control form-control-solid" maxlength="100">
                            <div id="state"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control form-control-solid" maxlength="20">
                            <div id="postal_code"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="accepts_marketing" id="customerEdit_accepts_marketing">
                                <label class="form-check-label" for="customerEdit_accepts_marketing">
                                    Accepts Marketing
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Status</label>
                            <select name="is_active" class="form-select form-select-solid">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <div id="is_active"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control form-control-solid" rows="2"></textarea>
                            <div id="notes"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="customerEditSubmit" class="btn btn-primary" style="min-width: 130px;">
                            <span class="indicator-label"><i class="bi bi-check2 me-1"></i>Update</span>
                            <span class="indicator-progress" style="display: none;">
                                Please wait… <span class="spinner-border spinner-border-sm ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>