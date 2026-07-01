<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\JsonResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        if ($request->ajax()) {
            return view('auth.login'); // Return partial view for AJAX
        }
        return view('layouts.guest', ['content' => view('auth.login')]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        
        // Authenticate the user
        $request->authenticate();

        // Check if the authenticated user's status is inactive
        if (auth()->user()->status === 'inactive') {
            // Call the destroy method to log out the user
            $this->destroy($request);

            // Return a response indicating the account is suspended
            return response()->json([
                'success' => false,
                'message' => __("Account Suspended, Contact Admin to re-establish it")
            ]);
        }

        // Clear application cache
        Artisan::call('optimize:clear');

        // Regenerate session to prevent session fixation attacks
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'reload' => false,
            'redirect' => route('dashboard'),
        ]);
        
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
