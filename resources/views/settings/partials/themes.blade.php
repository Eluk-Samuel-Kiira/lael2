<section>
    <form id="updateAppThemes" class="form">
        @csrf
        @method('patch')
        <div class="card-body border-top p-9">
            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.menu_color')}}</label>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12 fv-row">
                            <input type="color" name="menu_nav_color" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->menu_nav_color }}" />
                            <div id="menu_nav_color"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.font_family')}}</label>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12 fv-row">
                            <select name="font_family" class="form-select form-select-lg form-select-solid mb-3 mb-lg-0" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                <option value="">Select a font family</option>
                                <!-- Serif Fonts -->
                                <optgroup label="Serif Fonts">
                                    <option value="Times New Roman, Times, serif" {{ $app_info->font_family == 'Times New Roman, Times, serif' ? 'selected' : '' }}>Times New Roman</option>
                                    <option value="Georgia, serif" {{ $app_info->font_family == 'Georgia, serif' ? 'selected' : '' }}>Georgia</option>
                                    <option value="Palatino Linotype, Book Antiqua, Palatino, serif" {{ $app_info->font_family == 'Palatino Linotype, Book Antiqua, Palatino, serif' ? 'selected' : '' }}>Palatino</option>
                                    <option value="Garamond, serif" {{ $app_info->font_family == 'Garamond, serif' ? 'selected' : '' }}>Garamond</option>
                                    <option value="Baskerville, serif" {{ $app_info->font_family == 'Baskerville, serif' ? 'selected' : '' }}>Baskerville</option>
                                </optgroup>
                                
                                <!-- Sans-serif Fonts -->
                                <optgroup label="Sans-serif Fonts">
                                    <option value="Arial, Helvetica, sans-serif" {{ $app_info->font_family == 'Arial, Helvetica, sans-serif' ? 'selected' : '' }}>Arial</option>
                                    <option value="Helvetica, sans-serif" {{ $app_info->font_family == 'Helvetica, sans-serif' ? 'selected' : '' }}>Helvetica</option>
                                    <option value="Verdana, Geneva, sans-serif" {{ $app_info->font_family == 'Verdana, Geneva, sans-serif' ? 'selected' : '' }}>Verdana</option>
                                    <option value="Tahoma, Geneva, sans-serif" {{ $app_info->font_family == 'Tahoma, Geneva, sans-serif' ? 'selected' : '' }}>Tahoma</option>
                                    <option value="Trebuchet MS, Helvetica, sans-serif" {{ $app_info->font_family == 'Trebuchet MS, Helvetica, sans-serif' ? 'selected' : '' }}>Trebuchet MS</option>
                                    <option value="Geneva, sans-serif" {{ $app_info->font_family == 'Geneva, sans-serif' ? 'selected' : '' }}>Geneva</option>
                                </optgroup>
                                
                                <!-- Modern Fonts -->
                                <optgroup label="Modern Fonts">
                                    <option value="Segoe UI, Tahoma, Geneva, Verdana, sans-serif" {{ $app_info->font_family == 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif' ? 'selected' : '' }}>Segoe UI</option>
                                    <option value="Roboto, sans-serif" {{ $app_info->font_family == 'Roboto, sans-serif' ? 'selected' : '' }}>Roboto</option>
                                    <option value="Open Sans, sans-serif" {{ $app_info->font_family == 'Open Sans, sans-serif' ? 'selected' : '' }}>Open Sans</option>
                                    <option value="Lato, sans-serif" {{ $app_info->font_family == 'Lato, sans-serif' ? 'selected' : '' }}>Lato</option>
                                    <option value="Montserrat, sans-serif" {{ $app_info->font_family == 'Montserrat, sans-serif' ? 'selected' : '' }}>Montserrat</option>
                                    <option value="Raleway, sans-serif" {{ $app_info->font_family == 'Raleway, sans-serif' ? 'selected' : '' }}>Raleway</option>
                                    <option value="Poppins, sans-serif" {{ $app_info->font_family == 'Poppins, sans-serif' ? 'selected' : '' }}>Poppins</option>
                                </optgroup>
                                
                                <!-- Monospace Fonts -->
                                <optgroup label="Monospace Fonts">
                                    <option value="Courier New, Courier, monospace" {{ $app_info->font_family == 'Courier New, Courier, monospace' ? 'selected' : '' }}>Courier New</option>
                                    <option value="Lucida Console, Monaco, monospace" {{ $app_info->font_family == 'Lucida Console, Monaco, monospace' ? 'selected' : '' }}>Lucida Console</option>
                                    <option value="Monaco, monospace" {{ $app_info->font_family == 'Monaco, monospace' ? 'selected' : '' }}>Monaco</option>
                                </optgroup>
                                
                                <!-- Cursive/Fantasy Fonts -->
                                <optgroup label="Cursive & Fantasy">
                                    <option value="Brush Script MT, cursive" {{ $app_info->font_family == 'Brush Script MT, cursive' ? 'selected' : '' }}>Brush Script MT</option>
                                    <option value="Lucida Handwriting, cursive" {{ $app_info->font_family == 'Lucida Handwriting, cursive' ? 'selected' : '' }}>Lucida Handwriting</option>
                                    <option value="Comic Sans MS, cursive" {{ $app_info->font_family == 'Comic Sans MS, cursive' ? 'selected' : '' }}>Comic Sans MS</option>
                                </optgroup>
                            </select>
                            <div class="form-text mt-1">Select a font family for your application's text.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-6">
                <label class="col-lg-4 col-form-label fw-semibold fs-6">{{__('auth.font_size')}}</label>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12 fv-row">
                            <input type="number" name="font_size" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" value="{{ $app_info->font_size }}" />
                            <div id="font_size"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button 
                id="submitupdateTheme" 
                type="button" 
                class="btn btn-primary"
                onclick="submitSettingFormEntities('updateAppThemes', 'submitupdateTheme', '{{ route('setting.update') }}', 'PUT', '');">
                
                <span class="indicator-label">{{__('auth._update')}}</span>
                <span class="indicator-progress">{{__('auth.please_wait')}}
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
    </form>
</section>
