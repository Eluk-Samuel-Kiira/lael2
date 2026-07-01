
<!-- Settings Modal -->
<div class="modal fade" id="settingsTenant{{$tenant->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('payments.settings')}} - {{ $tenant->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                @if($tenant->settings && $tenant->settings->count() > 0)
                    @php
                        $groupedSettings = $tenant->settings->groupBy('category');
                    @endphp
                    
                    <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6" role="tablist">
                        @foreach($groupedSettings as $category => $settings)
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                    data-bs-toggle="tab" 
                                    href="#category{{ $tenant->id }}{{ Str::studly($category) }}" 
                                    role="tab">
                                    {{ ucfirst($category) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    
                    <div class="tab-content">
                        @foreach($groupedSettings as $category => $settings)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                    id="category{{ $tenant->id }}{{ Str::studly($category) }}" 
                                    role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered align-middle gy-4 gs-9">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800">
                                                <th>{{ __('payments.setting_key') }}</th>
                                                <th>{{ __('payments.setting_value') }}</th>
                                                <th>{{ __('payments.data_type') }}</th>
                                                <th>{{ __('payments.last_updated') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($settings as $setting)
                                                <tr>
                                                    <td class="fw-bold">{{ Str::title(str_replace('_', ' ', $setting->setting_key)) }}</td>
                                                    <td>
                                                        @if($setting->data_type == 'boolean')
                                                            <span class="badge badge-light-{{ $setting->setting_value ? 'success' : 'danger' }}">
                                                                {{ $setting->setting_value ? __('payments.yes') : __('payments.no') }}
                                                            </span>
                                                        @elseif($setting->data_type == 'json')
                                                            <pre class="mb-0"><code>{{ json_encode(json_decode($setting->setting_value), JSON_PRETTY_PRINT) }}</code></pre>
                                                        @else
                                                            {{ $setting->setting_value }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-info">{{ $setting->data_type }}</span>
                                                    </td>
                                                    <td>{{ $setting->updated_at ? \Carbon\Carbon::parse($setting->updated_at)->format('d M Y, h:i a') : 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle fs-1 me-2"></i>
                        {{ __('payments.no_settings') }}
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.close') }}</button>
            </div>
        </div>
    </div>
</div>