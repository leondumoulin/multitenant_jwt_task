<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:tenants,name',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|confirmed',
            'admin_password_confirmation' => 'required|string|min:8',
            'create_db_user' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'settings' => 'nullable|array',
            'settings.max_users' => 'nullable|integer|min:1|max:1000',
            'settings.features' => 'nullable|array',
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
            'name.required' => 'Tenant name is required.',
            'name.unique' => 'A tenant with this name already exists.',
            'admin_name.required' => 'Admin name is required.',
            'admin_email.required' => 'Admin email is required.',
            'admin_email.email' => 'Please provide a valid email address.',
            'admin_password.required' => 'Admin password is required.',
            'admin_password.min' => 'Password must be at least 8 characters.',
            'admin_password.confirmed' => 'Password confirmation does not match.',
            'admin_password_confirmation.required' => 'Password confirmation is required.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'tenant name',
            'admin_name' => 'admin name',
            'admin_email' => 'admin email',
            'admin_password' => 'admin password',
            'admin_password_confirmation' => 'password confirmation',
            'create_db_user' => 'create database user',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'create_db_user' => $this->boolean('create_db_user', false),
        ]);
    }
}
