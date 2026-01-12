<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $bladeToReload = $request->query('bladeFileToReload');
        switch ($bladeToReload) {
            case 'updatePasswordForm':
                return view('profile.partials.update-password-form', [
                    'user' => $request->user()
                ]);
            case 'updateProfileInfoForm':
                return view('profile.partials.update-profile-information-form', [
                    'user' => $request->user(),
                ]);
            default:
                return view('profile.edit', [
                    'user' => $request->user(),
                ]);
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        // Extract first_name and last_name from the request
        $firstName = $request->validated('first_name');
        $lastName = $request->validated('last_name');

        // Combine first_name and last_name into a single name field
        $fullName = trim($firstName . ' ' . $lastName);

        // Fill the user's other details and update the name field
        $request->user()->fill(array_merge(
            $request->validated(),
            ['name' => $fullName] // Add the combined name
        ));

        // Check if email was updated and set email_verified_at to null
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }
        
        $request->user()->save();

        // return Redirect::route('profile.edit')->with('status', 'profile-updated');
        return response()->json([
            'success' => true,
            'reload' => true,
            'componentId' => 'updateProfileInfoForm',
            'refresh' => false,
            'message' => __('profile-updated'),
            'redirect' => route('profile.edit'),
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->update(['status' => 'inactive']);
        Auth::logout();

        // Invalidate the session and regenerate the token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'reload' => false,
            'componentId' => 'updateProfileInfoForm',
            'refresh' => true,
            'message' => __('profile-updated'),
            'redirect' => route('profile.edit'),
        ]);
    }

    public function uploadImage(Request $request)
    {
        // Validate the request to ensure the file exists
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validation rules
        ]);

        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');

            // Get the currently authenticated user
            $user = auth()->user();

            // Check if the user already has a profile image and delete it
            if ($user->profile_image) {
                // Get the old image path from the user's profile
                $oldImagePath = public_path('storage/' . $user->profile_image); // Get full path to old image
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath); // Delete the old image from storage
                }
            }

            // Define the directory where the images will be stored
            $destinationPath = public_path('storage/profile_images');

            // Create the directory if it doesn't exist
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Store the new image in the defined directory
            $fileName = time() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $fileName);

            // Save the new image filename to the user's profile (not the URL)
            $user->profile_image = 'profile_images/' . $fileName; // Store relative path
            $user->save();

            // Respond with success if it's an AJAX request
            return response()->json([
                'success' => true,
                'message' => __('auth._uploaded'),
            ]);
        }

        // Return an error response if something goes wrong upload_failed
        return response()->json([
            'success' => false,
            'message' => __('auth.upload_failed '),
        ]);
    }
}
