<div class="modal fade" id="locationEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('auth._edit') }} {{ __('pagination._location') }} —
                    <span id="locationEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="locationEditForm" class="form">
                    @csrf
                    <input type="hidden" id="locationEditId">

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label required">Location Name</label>
                            <input type="text" name="name" class="form-control form-control-solid">
                            <div id="name"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-12">
                            <label class="form-label required">Address</label>
                            <input type="text" name="address" class="form-control form-control-solid">
                            <div id="address"></div>
                        </div>
                    </div>

                    <div class="row g-5 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Manager</label>
                            <select name="manager_id" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                                {{-- Populated server-side once with all users --}}
                                @foreach(\App\Models\User::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name']) as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <div id="manager_id"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Currency</label>
                            <select name="currency_id" class="form-select form-select-solid">
                                <option value="">— Select —</option>
                                @foreach(\App\Models\Currency::all(['id', 'code', 'name']) as $c)
                                    <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                                @endforeach
                            </select>
                            <div id="currency_id"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                        <button type="button" id="locationEditSubmit" class="btn btn-primary" style="min-width: 130px;">
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