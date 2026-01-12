<x-guest-layout>
    @section('title', __('Reset Password'))
    @section('content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">{{__('Reset Password')}}</h1>
        <div class="text-gray-500 fw-semibold fs-6">{{__('Lost Your Password? Never Worry, Just Enter New Password')}}</div>
    </div>

    <form class="form w-100" novalidate="novalidate" id="reset_password_form">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="fv-row mb-8">
            <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent" />
            <div id="email"></div>
        </div>
        <div class="fv-row mb-3">
            <input type="password" placeholder="Password" name="password" autocomplete="off" class="form-control bg-transparent" />
            <div id="password"></div>
        </div>
        <div class="fv-row mb-3">
            <input type="password" placeholder="Confirm Password" name="password_confirmation" autocomplete="off" class="form-control bg-transparent" />
            <div id="password_confirmation"></div>
        </div>
        <div class="d-grid mb-10">
            <button id="resetPasswordButton" onclick="passwordResetAction()" type="button" class="btn btn-primary">
                <span class="indicator-label">{{__('Reset Password')}}</span>
                <span class="indicator-progress" style="display: none;">{{__('Please wait... ')}}
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>
        </div>
    </form>
    
    @endsection
</x-guest-layout>
