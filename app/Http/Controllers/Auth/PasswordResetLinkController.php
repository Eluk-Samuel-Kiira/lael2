<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
            ]);

            // We will send the password reset link to this user.
            $status = Password::sendResetLink(
                $request->only('email')
            );

            // Check if the reset link was sent successfully
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => __($status),
                    'redirect' => route('login')
                ]);
            }

            // If there was an error
            return response()->json([
                'success' => false,
                'message' => __($status),
                'errors' => [
                    'email' => [__($status)]
                ]
            ], 422);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Password reset link error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset link. Please try again.',
                'errors' => [
                    'email' => ['Failed to send password reset link. Please try again.']
                ]
            ], 500);
        }
    }
}