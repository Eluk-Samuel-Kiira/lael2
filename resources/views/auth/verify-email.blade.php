<x-guest-layout>
    @section('title', __('Verify Email'))
    @section('content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">{{ __('Verify Your Email Address') }}</h1>
        <div class="text-gray-500 fw-semibold fs-6">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </div>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success d-flex align-items-center mb-5" role="alert">
            <i class="bi bi-check-circle-fill fs-2 me-3"></i>
            <div>
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="form w-100">
        @csrf
        <div class="d-grid mb-5">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">{{ __('Resend Verification Email') }}</span>
            </button>
        </div>
    </form>

    <div class="text-center">
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link text-gray-600 text-decoration-none">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
    @endsection
</x-guest-layout>