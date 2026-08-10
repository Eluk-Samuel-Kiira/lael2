 
<div class="modal fade" id="kt_modal_add_department" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-550px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_currency">
                <h2 class="fw-bold">{{__('auth._department_new')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_currency_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._name')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="name" />
                                <div id="name"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <!-- Manager -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth._manager')}}</span>
                                </label>
                                @php
                                    $formattedUsers = [];
                                    foreach($users as $user) {
                                        $formattedUsers[] = (object)[
                                            'id' => $user->id,
                                            'name' => $user->name
                                        ];
                                    }
                                @endphp
                                <x-typable-select 
                                    name="manager_id"
                                    :options="$formattedUsers"
                                    placeholder="Type or select manager..."
                                />
                                <div id="manager_id"></div>
                            </div>

                            <!-- Location -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('auth.location')}}</span>
                                </label>
                                @php
                                    $formattedLocations = [];
                                    foreach($locations as $location) {
                                        $formattedLocations[] = (object)[
                                            'id' => $location->id,
                                            'name' => $location->name
                                        ];
                                    }
                                @endphp
                                <x-typable-select 
                                    name="location_id"
                                    :options="$formattedLocations"
                                    placeholder="Type or select location..."
                                />
                                <div id="location_id"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination.department_type')}}</label>
                                <select name="department_type" class="form-select">
                                    <option></option>
                                    <option value="retail">{{__('pagination.retail')}}</option>
                                    <option value="electronics">{{__('pagination.electronics')}}</option>
                                    <option value="pharmacy">{{__('pagination.pharmacy')}}</option>
                                    <option value="restaurant">{{__('pagination.restaurant')}}</option>
                                    <option value="manufacturing">{{__('pagination.manufacturing')}}</option>
                                </select>
                                <div id="department_type"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="required form-label">{{__('pagination.default_inventory_strategy')}}</label>
                                <select name="default_inventory_strategy" class="form-select">
                                    <option></option>
                                    <option value="quantity">{{__('pagination.quantity')}}</option>
                                    <option value="batch">{{__('pagination.batch')}}</option>
                                    <option value="serial">{{__('pagination.serial')}}</option>
                                    <option value="recipe">{{__('pagination.recipe')}}</option>
                                </select>
                                <div id="default_inventory_strategy"></div>
                            </div>
                        </div>

                        <button type="reset" class="btn btn-light me-3" id="discardButton" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                        <button 
                            id="submitCurrencyButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitFormDept('kt_modal_add_currency_form', 'submitCurrencyButton', '{{ route('department.store') }}', 'POST', 'discardButton')">
                            
                            <span class="indicator-label">{{__('auth.submit')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>  


