<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreDealRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'value' => 'required|numeric|min:0|max:999999999.99',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'status' => 'nullable|in:open,closed,won,lost,negotiation,proposal',
            'probability' => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date|after:today',
            'actual_close_date' => 'nullable|date|after_or_equal:expected_close_date',
            'stage' => 'nullable|string|max:100',
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
            'title.required' => 'Deal title is required.',
            'value.required' => 'Deal value is required.',
            'value.numeric' => 'Deal value must be a number.',
            'value.min' => 'Deal value must be at least 0.',
            'value.max' => 'Deal value cannot exceed 999,999,999.99.',
            'contact_id.exists' => 'The selected contact does not exist.',
            'status.in' => 'Status must be one of: open, closed, won, lost, negotiation, proposal.',
            'probability.integer' => 'Probability must be an integer.',
            'probability.min' => 'Probability must be at least 0.',
            'probability.max' => 'Probability cannot exceed 100.',
            'expected_close_date.after' => 'Expected close date must be in the future.',
            'actual_close_date.after_or_equal' => 'Actual close date must be on or after the expected close date.',
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
            'title' => 'deal title',
            'description' => 'deal description',
            'value' => 'deal value',
            'contact_id' => 'contact',
            'status' => 'deal status',
            'probability' => 'win probability',
            'expected_close_date' => 'expected close date',
            'actual_close_date' => 'actual close date',
            'stage' => 'deal stage',
            'source' => 'deal source',
            'tags' => 'deal tags',
            'custom_fields' => 'custom fields',
        ];
    }
}
