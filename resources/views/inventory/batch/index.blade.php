<x-app-layout>
    @section('title', __('passwords.batch_inventory'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('passwords.batch_inventory')}}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        @php
                            $previousUrl = url()->previous();
                            $previousRouteName = optional(app('router')->getRoutes()->match(request()->create($previousUrl)))->getName();
                            $formattedRouteName = $previousRouteName 
                                ? Str::of($previousRouteName)->replace('.', ' ')->title() 
                                : __('auth._back');
                        @endphp
                        <a href="{{ $previousUrl }}" class="text-muted text-hover-primary">
                            {{ $formattedRouteName }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('passwords.batch_inventory')}}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">

                {{-- Search --}}
                <div class="w-100 w-sm-250px">
                    <x-liveblade-search
                        id="batchSearchInput"
                        componentId="reloadBatchComponent"
                        route="{{ route('batches.index') }}"
                        placeholder="{{ __('passwords.search_batches') }}"
                    />
                </div>

                {{-- Location filter --}}
                @if($locations->count() > 0)
                    <div class="w-100 w-sm-200px">
                        <select id="batchLocationFilter"
                                class="form-select form-select-solid"
                                data-control="select2"
                                data-placeholder="{{ __('passwords.all_locations') }}"
                                data-allow-clear="true"
                                onchange="filterBatchesByLocation(this.value)">
                            <option value="">{{ __('passwords.all_locations') }}</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" @selected((int) $locationId === (int) $loc->id)>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @can('edit inventory')
                    <button type="button" class="btn btn-primary flex-shrink-0" onclick="assignSelectedBatches()">
                        <i class="ki-duotone ki-tag fs-2 me-2 me-sm-3"></i>
                        <span class="d-none d-sm-inline">{{ __('passwords.assign_batches') }}</span>
                        <span class="d-inline d-sm-none">{{ __('passwords.assign') }}</span>
                    </button>
                @endcan

                @include('inventory.batch.assign-modal')
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    @include('inventory.batch.batch-component')

                </div>
            </div>
        </div>
    </div>
    <script>
        function filterBatchesByLocation(locationId) {
            const url = new URL(window.location.href);

            if (locationId) {
                url.searchParams.set('location_id', locationId);
            } else {
                url.searchParams.delete('location_id');
            }

            // Reset to first page when filtering
            url.searchParams.delete('page');

            // If your search input has a value, preserve it
            const searchInput = document.getElementById('batchSearchInput');
            if (searchInput && searchInput.value) {
                url.searchParams.set('search', searchInput.value);
            } else {
                url.searchParams.delete('search');
            }

            window.location.href = url.toString();
        }
    </script>
    
    @endsection
</x-app-layout>