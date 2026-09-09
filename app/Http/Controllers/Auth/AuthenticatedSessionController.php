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
use App\Models\Tenant;

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

        $user = auth()->user();

        // ✅ Check if the authenticated user's status is inactive
        if ($user->status === 'inactive') {
            // Call the destroy method to log out the user
            $this->destroy($request);

            // Return a response indicating the account is suspended
            return response()->json([
                'success' => false,
                'message' => __("Your account is suspended. Please contact your administrator.")
            ]);
        }

        // ✅ Check if the tenant is active or inactive
        if ($user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
            
            if ($tenant) {
                // Check if tenant status is inactive, suspended, or expired
                if (in_array($tenant->status, ['inactive', 'suspended', 'expired'])) {
                    // Log the user out
                    $this->destroy($request);
                    
                    $statusMessages = [
                        'inactive' => 'Your organization account is currently inactive.',
                        'suspended' => 'Your organization account has been suspended.',
                        'expired' => 'Your organization subscription has expired.',
                    ];
                    
                    $message = $statusMessages[$tenant->status] ?? 'Your organization account is not active.';
                    
                    return response()->json([
                        'success' => false,
                        'message' => __($message . ' Please contact your system administrator.')
                    ]);
                }
            }
        }

        // ✅ Check if tenant has subscription status
        if ($user->tenant_id) {
            $subscriptionStatus = \App\Models\TenantSetting::where('tenant_id', $user->tenant_id)
                ->where('setting_key', 'subscription_status')
                ->first();
            
            if ($subscriptionStatus && in_array($subscriptionStatus->setting_value, ['inactive', 'expired', 'suspended'])) {
                // Log the user out
                $this->destroy($request);
                
                $statusMessages = [
                    'inactive' => 'Your organization subscription is inactive.',
                    'expired' => 'Your organization subscription has expired.',
                    'suspended' => 'Your organization subscription has been suspended.',
                ];
                
                $message = $statusMessages[$subscriptionStatus->setting_value] ?? 'Your organization subscription is not active.';
                
                return response()->json([
                    'success' => false,
                    'message' => __($message . ' Please contact your system administrator to renew.')
                ]);
            }
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