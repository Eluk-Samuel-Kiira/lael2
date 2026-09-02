<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\ValidPhoneNumber;

class StoreEmployeeRequest extends FormRequest
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
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|integer|exists:departments,id',
            'location_id' => 'required|integer|exists:locations,id',
            'telephone_number' => [
                'required',
                'string',
                'max:20',
                new ValidPhoneNumber(),
                Rule::unique('users', 'telephone_number'),
            ],
            'job_title' => 'nullable|string|max:255',
        ];
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
}