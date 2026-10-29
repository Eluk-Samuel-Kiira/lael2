<div class="modal fade" id="deptEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('auth._edit_department') }} —
                    <span id="deptEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="deptEditForm" class="form">
                    @csrf
                    <input type="hidden" id="deptEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control form-control-solid">
                            <div id="name"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Manager</label>
                            <select name="manager_id" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                                @foreach(\App\Models\User::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name']) as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <div id="manager_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Location</label>
                            <select name="location_id" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                                @foreach(\App\Models\Location::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name']) as $l)
                                    <option value="{{ $l->id }}">{{ $l->name }}</option>
                                @endforeach
                            </select>
                            <div id="location_id"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Department Type</label>
                            <select name="department_type" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                                <option value="retail">Retail</option>
                                <option value="electronics">Electronics</option>
                                <option value="pharmacy">Pharmacy</option>
                                <option value="restaurant">Restaurant</option>
                                <option value="manufacturing">Manufacturing</option>
                            </select>
                            <div id="department_type"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Strategy</label>
                            <select name="default_inventory_strategy" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                                <option value="quantity">Quantity</option>
                                <option value="batch">Batch</option>
                                <option value="serial">Serial</option>
                                <option value="recipe">Recipe</option>
                            </select>
                            <div id="default_inventory_strategy"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="deptEditSubmit" class="btn btn-primary" style="min-width: 130px;">
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