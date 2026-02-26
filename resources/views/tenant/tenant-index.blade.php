<x-app-layout>
    @section('title', __('payments.tenant_index'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('payments.tenant_table')}}</h1>
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
                    <li class="breadcrumb-item text-muted">{{__('payments.tenant_index')}}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <!-- Search Bar -->
                <div class="px-7 py-5">
                    <input type="text" id="searchInput" class="form-control" placeholder="{{__('auth._search')}} {{__('payments.tenant')}}"
                        onkeyup="searchTable(this.value, 'kt_table_users')">
                </div>
                
                @role('super_admin')
                    <a href="{{ route('tenant.create') }}" class="btn btn-sm btn-primary">
                        <i class="ki-duotone ki-plus fs-2"></i>{{__('payments.new_tenant')}}
                    </a>
                
                    <!-- Refresh Billing Plans Button -->
                    <button type="button" class="btn btn-sm btn-light-warning" id="refreshPlansBtn" onclick="refreshBillingPlans()">
                        <i class="ki-duotone ki-arrows-circle fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{__('payments.refresh_plans')}}
                    </button>
                @endrole
            </div>

            @push('scripts')
            <script>
                function refreshBillingPlans() {
                    Swal.fire({
                        title: '{{ __("payments.confirm_refresh_plans") }}',
                        text: '{{ __("payments.refresh_plans_warning") }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '{{ __("payments.yes_refresh") }}',
                        cancelButtonText: '{{ __("auth._discard") }}',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return fetch('{{ route("billing.refresh-plans") }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(response.statusText);
                                }
                                return response.json();
                            })
                            .catch(error => {
                                Swal.showValidationMessage(
                                    `{{ __("payments.refresh_failed") }}: ${error}`
                                );
                            });
                        },
                        allowOutsideClick: () => !Swal.isLoading()
                    }).then((result) => {
                        if (result.isConfirmed && result.value && result.value.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __("payments.refresh_success") }}',
                                text: result.value.message || '{{ __("payments.plans_refreshed") }}',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Optionally reload the page or update the UI
                                location.reload();
                            });
                        } else if (result.isConfirmed && result.value && !result.value.success) {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("payments.refresh_failed") }}',
                                text: result.value.message || '{{ __("payments.unknown_error") }}'
                            });
                        }
                    });
                }
            </script>
            @endpush
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                <div id="status"></div>
                <div class="card">
                    @include('tenant.partials.component')
                </div>
            </div>
        </div>
    </div>
 
        
    @endsection
</x-app-layout>