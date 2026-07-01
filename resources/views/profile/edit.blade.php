<x-app-layout>
    @section('title', __('auth._pro_info'))
    @section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('auth._pro_setting')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        @php
                            $previousUrl = url()->previous();
                            $previousRouteName = optional(app('router')->getRoutes()->match(request()->create($previousUrl)))->getName();
                            $formattedRouteName = $previousRouteName 
                                ? Str::of($previousRouteName)->replace('.', ' ')->title() 
                                : __('Back');
                        @endphp
                        <a href="{{ $previousUrl }}" class="text-muted text-hover-primary">
                            {{ $formattedRouteName }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('auth._account')}}</li>
                </ul>
            </div>
        </div>
    </div>

    
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card mb-5 mb-xl-10">
                <div class="card-body pt-9 pb-0">
                    <div class="d-flex flex-wrap flex-sm-nowrap">
                        <div class="me-7 mb-4">
                            <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                                <img src="{{ getProfileImage() }}" alt="image" />
                                <div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px"></div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                <div class="d-flex flex-column">
                                    <div class="d-flex align-items-center mb-2">
                                        <a href="#" class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">{{ $user->name }}</a>
                                        <a href="#">
                                            <i class="ki-duotone ki-verify fs-1 text-primary">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </a>
                                    </div>
                                    <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                        <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                        <i class="ki-duotone ki-profile-circle fs-4 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>{{ ucwords(str_replace('_', ' ', $user->role)) }}</a>
                                        <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                        <i class="ki-duotone ki-geolocation fs-4 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>{{ $user->telephone_number }}</a>
                                        <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary mb-2">
                                        <i class="ki-duotone ki-sms fs-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>{{ $user->email }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold" id="account_tabs">
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5 active" id="profile-tab" data-bs-toggle="tab" href="#profile">{{__('auth._pro_info')}}</a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5" id="password-tab" data-bs-toggle="tab" href="#update-password">{{__('auth.update_password')}}</a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="nav-link text-active-primary ms-0 me-10 py-5" id="deactivate-tab" data-bs-toggle="tab" href="#deactivate">{{__('auth.account_deactivate')}}</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content mt-5" id="account_tabs_content">
                <!-- Profile Information Tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card mb-5 mb-xl-10">
                        <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details">
                            <div class="card-title m-0">
                                <h3 class="fw-bold m-0">{{__('auth._pro_info')}}</h3>
                            </div>
                        </div>
                        <div id="kt_account_settings_profile_details" class="collapse show">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                </div>

                <!-- Update Password Tab -->
                <div class="tab-pane fade" id="update-password">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">{{__('auth.update_password')}}</h3>
                        </div>
                        <div class="card-body">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </div>

                <!-- Account Deactivation Tab -->
                <div class="tab-pane fade" id="deactivate">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">{{__('auth.account_deactivate')}}</h3>
                        </div>
                        <div class="card-body">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const submitFormEntities = (formId, submitButtonId, url, method) => {
            document.getElementById(formId).addEventListener('submit', function(e) {
                e.preventDefault();

                // Collect form data and add additional fields
                const formData = Object.fromEntries(new FormData(this));

                formData._method = method;
                formData.routeName = url;

                // Reference the submit button and reloading
                const submitButton = document.getElementById(submitButtonId);
                LiveBlade.toggleButtonLoading(submitButton, true);

                // Submit form data asynchronously
                LiveBlade.submitFormItems(formData)
                    .then(noErrors => {
                        console.log(noErrors);
                        
                        if (noErrors) {
                            // Close the modal if no errors
                            const closeModal = () => {
                                document.getElementById('discardButton').click();
                            };
                            closeModal();
                        }
                    })
                    .catch(error => {
                        console.error('An unexpected error occurred:', error);
                    })
                    .finally(() => {
                        LiveBlade.toggleButtonLoading(submitButton, false);
                    });

                    
            });
        };

        submitFormEntities('updateProfileInfoForm', 'submitProfileButton', '{{ route('profile.update') }}', 'PATCH');
        submitFormEntities('updatePasswordForm', 'submitPasswordButton', '{{ route('password.update') }}', 'PUT');
        submitFormEntities('deleteAccountForm', 'submitDeleteButton', '{{ route('profile.destroy') }}', 'DELETE');
    </script>

    
    @endsection
</x-app-layout>
