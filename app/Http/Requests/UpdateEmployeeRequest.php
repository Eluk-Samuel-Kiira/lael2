<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'department_id' => 'required|integer|exists:departments,id',
            'location_id' => 'required|integer|exists:locations,id',
            'telephone_number' => 'required|string|max:20',
            'job_title' => 'nullable',
        ];

        // Check if the authenticated user has admin role
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole('admin');
        
        // If user is admin, make role_id nullable
        // If user is super_admin, keep role_id as required/optional as needed
        if ($isAdmin) {
            $rules['role_id'] = 'nullable|exists:roles,id';
        } else {
            // For super_admin, role_id is optional but can be provided
            $rules['role_id'] = 'sometimes|exists:roles,id';
        }

        return $rules;
    }
}