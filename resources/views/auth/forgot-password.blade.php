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
            <button id="passwordResetButton" onclick="passwordResetEmailAction()" type="button" class="btn btn-primary">
                <span class="indicator-label">{{ __('Email Password Reset Link') }}</span>
                <span class="indicator-progress" style="display: none;">{{__('Please wait... ')}}
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>
        </div>
    </form>
    @endsection
</x-guest-layout>
