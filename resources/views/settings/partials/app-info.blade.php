<section>
    <form id="updateAppInfoForm" class="form">
        @csrf
        @method('patch')
        <div class="card-body border-top p-9">

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.app_name') }}</label>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12 fv-row">
                            <input type="text" name="app_name" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->app_name }}" />
                            <div id="app_name"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.app_email') }}</label>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12 fv-row">
                            <input type="text" name="app_email" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->app_email }}" />
                            <div id="app_email"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.app_contact') }}</label>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12 fv-row">
                            <input type="number" name="app_contact" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->app_contact }}" />
                            <div id="app_contact"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.app_currency') }}</label>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12 fv-row">
                            <input type="text" 
                                name="currency" 
                                class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" 
                                value="{{ tenant_currency() }}" 
                                readonly/>
                            <div id="currency"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button 
                id="submitAppInfo" 
                type="button" 
                class="btn btn-primary"
                onclick="submitSettingFormEntities('updateAppInfoForm', 'submitAppInfo', '{{ route('setting.update') }}', 'PUT', '');">
                
                <span class="indicator-label">{{__('auth._update')}}</span>
                <span class="indicator-progress">{{__('auth.please_wait')}}
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
        
    </form>

</section>