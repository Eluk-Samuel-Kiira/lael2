<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Rules\ValidPhoneNumber;
use Illuminate\Support\Facades\Log;

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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // 🔍 DEBUG: Log all input data
        // Log::info('🔍 UpdateEmployeeRequest - All input:', [
        //     'all' => $this->all(),
        //     'telephone_number' => $this->input('telephone_number'),
        //     'method' => $this->method(),
        //     'route' => $this->route()->getName(),
        // ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('employee');
        
        // Log::info('🔍 UpdateEmployeeRequest - Building rules for user:', [
        //     'userId' => $userId,
        // ]);
        
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'department_id' => 'required|integer|exists:departments,id',
            'location_id' => 'required|integer|exists:locations,id',
            'telephone_number' => [
                'required',
                'string',
                'max:20',
                new ValidPhoneNumber(),
                Rule::unique('users', 'telephone_number')->ignore($userId),
            ],
            'job_title' => 'nullable|string|max:255',
        ];

        $user = Auth::user();
        $isAdmin = $user && $user->hasRole('admin');
        
        // Log::info('🔍 UpdateEmployeeRequest - User role check:', [
        //     'user_id' => $user->id,
        //     'isAdmin' => $isAdmin,
        // ]);
        
        if ($isAdmin) {
            $rules['role_id'] = 'nullable|exists:roles,id';
        } else {
            $rules['role_id'] = 'sometimes|exists:roles,id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'telephone_number.required' => 'The phone number is required.',
            'telephone_number.unique' => 'This phone number is already registered.',
            'telephone_number.max' => 'The phone number cannot exceed 20 characters.',
        ];
    }

    /**
     * Get the validated data with debugging.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Log::info('🔍 UpdateEmployeeRequest - Validated data:', [
        //     'validated' => $validated,
        // ]);
        
        return $validated;
    }
}