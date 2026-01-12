<x-guest-layout>
    @section('title', __('Login Page'))
    @section('content')

    <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form">
        @csrf
        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">{{__('Sign In')}}</h1>
            <div class="text-gray-500 fw-semibold fs-6">{{__('Enter Your Email And Password')}}</div>
        </div>

        <div class="fv-row mb-8">
            <input type="text" placeholder="Email" name="email" id="emailField" autocomplete="off" class="form-control bg-transparent" />
            <div id="email"></div>
        </div>

        <div class="fv-row mb-3">
            <input type="password" placeholder="Password" name="password" id="passwordField" autocomplete="off" class="form-control bg-transparent" />
            <div id="password"></div>
        </div>

        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            <div></div>
            <a href="javascript:void(0)" class="link-primary" onclick="navigateToGuestPage('{{route('password.request')}}')">
                {{__('Forgot Password ?')}}
            </a>
        </div>

        <div class="d-grid mb-10">
            <button id="loginSubmitButton" onclick="loginAction()" type="button" class="btn btn-primary">
                <span class="indicator-label">{{__('Sign In')}}</span>
                <span class="indicator-progress" style="display: none;">{{__('Please wait... ')}}
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>
        </div>

        <!-- Demo credentials (auto-fill when clicked) -->
        <div class="text-center">
            <p class="text-gray-400 fw-semibold mb-2">{{ __('For Demo Access') }}</p>
            <button type="button" 
                onclick="fillDemoCredentials()" 
                class="btn btn-outline-primary btn-sm fw-semibold px-4 py-2 rounded-pill shadow-sm d-inline-flex align-items-center gap-2">
                <i class="fas fa-magic"></i>
                <span>Click to Auto-Fill: <strong>trialuser@gmail.com / password@123</strong></span>
            </button>
            <p class="text-muted fs-7 mt-2">Tap to fill demo credentials automatically</p>
        </div>
    </form>

    <script>
        function fillDemoCredentials() {
            document.getElementById('emailField').value = 'trialuser@gmail.com';
            document.getElementById('passwordField').value = 'password@123';
        }
    </script>

    @endsection
</x-guest-layout>
