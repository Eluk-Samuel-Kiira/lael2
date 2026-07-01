<section>
    <form id="updateSMTPForm" class="form">
        @csrf
        @method('patch')
        <div class="card-body border-top p-9">
            <div class="row">
                <!-- Column 1 -->
                <div class="col-lg-6">
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.mail_mailer') }}</label>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-lg-12 fv-row">
                                    <input type="text" name="mail_mailer" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->mail_mailer }}" />
                                    <div id="mail_mailer"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.mail_host') }}</label>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-lg-12 fv-row">
                                    <input type="text" name="mail_host" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->mail_host }}" />
                                    <div id="mail_host"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.mail_name') }}</label>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-lg-12 fv-row">
                                    <input type="text" name="mail_name" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->mail_name }}" />
                                    <div id="mail_name"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.mail_password') }}</label>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-lg-12 fv-row">
                                    <input type="text" name="mail_password" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->mail_password }}" />
                                    <div id="mail_password"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Column 2 -->
                <div class="col-lg-6">
                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.mail_port') }}</label>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-lg-12 fv-row">
                                    <input type="text" name="mail_port" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->mail_port }}" />
                                    <div id="mail_port"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.mail_username') }}</label>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-lg-12 fv-row">
                                    <input type="text" name="mail_username" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->mail_username }}" />
                                    <div id="mail_username"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.mail_address') }}</label>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-lg-12 fv-row">
                                    <input type="text" name="mail_address" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->mail_address }}" />
                                    <div id="mail_address"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-6">
                        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.mail_status') }}</label>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-lg-12 fv-row">
                                    <select name="mail_status" class="form-control" id="mail_status">
                                        <option value="enabled" {{ $app_info->mail_status == 'enabled' ? 'selected' : '' }}>{{__('auth._enabled') }}</option>
                                        <option value="disabled" {{ $app_info->mail_status == 'disabled' ? 'selected' : '' }}>{{__('auth._disabled') }}</option>
                                    </select>
                                    <div id="mail_status"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button 
                id="submitupdateSMTP" 
                type="button" 
                class="btn btn-primary"
                onclick="submitSettingFormEntities('updateSMTPForm', 'submitupdateSMTP', '{{ route('setting.update') }}', 'PUT', '');">
                
                <span class="indicator-label">{{__('auth._update')}}</span>
                <span class="indicator-progress">{{__('auth.please_wait')}}
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
    </form>
</section>
