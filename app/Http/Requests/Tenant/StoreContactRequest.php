<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[\+]?[1-9][\d]{0,15}$/',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'status' => 'nullable|in:active,inactive,lead,prospect',
            'source' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'custom_fields' => 'nullable|array',
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
            'name.required' => 'Contact name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'phone.regex' => 'Please provide a valid phone number.',
            'status.in' => 'Status must be one of: active, inactive, lead, prospect.',
            'tags.array' => 'Tags must be an array.',
            'tags.*.string' => 'Each tag must be a string.',
            'tags.*.max' => 'Each tag must not exceed 50 characters.',
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
            'name' => 'contact name',
            'email' => 'email address',
            'phone' => 'phone number',
            'company' => 'company name',
            'position' => 'job position',
            'notes' => 'notes',
            'status' => 'contact status',
            'source' => 'lead source',
            'tags' => 'contact tags',
            'custom_fields' => 'custom fields',
        ];
    }
}
