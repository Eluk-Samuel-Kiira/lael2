<x-guest-layout>
    @section('title', __('Reset Password'))
    @section('content')
    <div class="text-center mb-11">
        <div id="status"></div>
        <h1 class="text-gray-900 fw-bolder mb-3">{{__('Forgot Password ?')}}</h1>
        <div class="text-gray-500 fw-semibold fs-6">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </div>
    </div>

    <form class="form w-100" novalidate="novalidate" id="forgot_password_form">
        @csrf
        <div class="fv-row mb-8">
            <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent" />
            <div id="email"></div>
        </div>
        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary" id="submitButton">
                <span class="indicator-label">{{ __('Email Password Reset Link') }}</span>
                <span class="indicator-progress" style="display: none;">{{__('Please wait... ')}}
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>
        </div>
    </form>

    <script>
        // Laravel routes and form handling to be pass to js

        const handleFormSubmit = (formId, submitButtonId, routeName, method = 'POST') => {
            document.getElementById(formId).addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = Object.fromEntries(new FormData(this));
                // Append routeName, method, and formId to formData
                formData.routeName = routeName;
                formData.formId = `#${formId}`;
                formData._method = method;

                // console.log(formData)
                
                const submitButton = document.getElementById(submitButtonId);
                LiveBlade.toggleButtonLoading(submitButton, true);

                LiveBlade.submitFormItems(formData)
                    .then(noErrors => {
                        console.log(noErrors);
                        // nothing
                    })
                    .catch(error => {
                        console.error('An unexpected error occurred:', error);
                    })
                    .finally(() => {
                        LiveBlade.toggleButtonLoading(submitButton, false);
                    });

            });
        };
        handleFormSubmit('forgot_password_form', 'submitButton', '{{ route('password.email') }}');
    </script>

    @endsection

    {{--
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
    --}}
</x-guest-layout>
