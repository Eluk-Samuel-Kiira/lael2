<div class="modal fade" id="pmEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('payments.edit_payment_method') }} —
                    <span id="pmEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="pmEditForm" class="form">
                    @csrf
                    <input type="hidden" id="pmEditId">

                    {{-- Basic fields --}}
                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control form-control-solid">
                            <div id="name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code" class="form-control form-control-solid">
                            <div id="code"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Type</label>
                            <select name="type" class="form-select form-select-solid">
                                <option value="bank_account">Bank Account</option>
                                <option value="digital_wallet">Digital Wallet</option>
                                <option value="card">Card</option>
                                <option value="cash">Cash</option>
                                <option value="check">Check</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="other">Other</option>
                            </select>
                            <div id="type"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Provider</label>
                            <input type="text" name="provider" class="form-control form-control-solid">
                            <div id="provider"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Account Name</label>
                            <input type="text" name="account_name" class="form-control form-control-solid">
                            <div id="account_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" class="form-control form-control-solid">
                            <div id="account_number"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Current Balance</label>
                            <input type="number" step="0.01" name="current_balance" class="form-control form-control-solid">
                            <div id="current_balance"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control form-control-solid" rows="2"></textarea>
                            <div id="description"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Available Locations <span class="text-muted fs-7 fw-normal">(leave empty for all locations)</span></label>
                            <select name="location_id[]" class="form-select form-select-solid" multiple data-control="select2" data-placeholder="Select locations">
                                @foreach(\App\Models\Location::where('tenant_id', auth()->user()->tenant_id)->where('is_active', 1)->orderBy('name')->get(['id', 'name']) as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                            <div id="location_id"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Transaction Fee %</label>
                            <input type="number" step="0.01" min="0" max="100" name="transaction_fee_percentage" class="form-control form-control-solid">
                            <div id="transaction_fee_percentage"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fixed Fee</label>
                            <input type="number" step="0.01" min="0" name="transaction_fee_fixed" class="form-control form-control-solid">
                            <div id="transaction_fee_fixed"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Min Amount</label>
                            <input type="number" step="0.01" min="0" name="min_transaction_amount" class="form-control form-control-solid">
                            <div id="min_transaction_amount"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Amount</label>
                            <input type="number" step="0.01" min="0" name="max_transaction_amount" class="form-control form-control-solid">
                            <div id="max_transaction_amount"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_active" id="pmEdit_is_active">
                                <label class="form-check-label" for="pmEdit_is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_default" id="pmEdit_is_default">
                                <label class="form-check-label" for="pmEdit_is_default">Set as Default</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_online" id="pmEdit_is_online">
                                <label class="form-check-label" for="pmEdit_is_online">Online</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="pmEditSubmit" class="btn btn-primary" style="min-width: 130px;">
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