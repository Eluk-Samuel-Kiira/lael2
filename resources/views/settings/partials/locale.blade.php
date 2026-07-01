
<div class="card-body border-top p-9">
    <div class="row mb-6">
        <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.select_lang')}}</label>
        <div class="col-lg-8">
            <div class="row">
                <div class="col-lg-12 fv-row">
                    <select name="locale" class="form-control" onchange="changeLocale(this.value)">
                        <option value="en" {{ $app_info->locale == 'en' ? 'selected' : '' }}>{{__('auth.english')}}</option>
                        <option value="fr" {{ $app_info->locale == 'fr' ? 'selected' : '' }}>{{__('auth.french')}}</option>
                    </select>
                    <div id="locale"></div>
                </div>
            </div>
        </div>
    </div>
</div>
