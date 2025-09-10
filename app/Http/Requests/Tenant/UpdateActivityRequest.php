<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
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
            'type' => 'sometimes|required|in:call,email,meeting,task,note,other',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'deal_id' => 'nullable|integer|exists:deals,id',
            'scheduled_at' => 'nullable|date',
            'completed_at' => 'nullable|date|after_or_equal:scheduled_at',
            'status' => 'nullable|in:pending,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'duration' => 'nullable|integer|min:1|max:1440', // minutes
            'location' => 'nullable|string|max:255',
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
            'type.required' => 'Activity type is required.',
            'type.in' => 'Activity type must be one of: call, email, meeting, task, note, other.',
            'title.required' => 'Activity title is required.',
            'contact_id.exists' => 'The selected contact does not exist.',
            'deal_id.exists' => 'The selected deal does not exist.',
            'completed_at.after_or_equal' => 'Completion time must be on or after the scheduled time.',
            'status.in' => 'Status must be one of: pending, completed, cancelled.',
            'priority.in' => 'Priority must be one of: low, medium, high, urgent.',
            'duration.integer' => 'Duration must be an integer.',
            'duration.min' => 'Duration must be at least 1 minute.',
            'duration.max' => 'Duration cannot exceed 1440 minutes (24 hours).',
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
            'type' => 'activity type',
            'title' => 'activity title',
            'description' => 'activity description',
            'contact_id' => 'contact',
            'deal_id' => 'deal',
            'scheduled_at' => 'scheduled time',
            'completed_at' => 'completion time',
            'status' => 'activity status',
            'priority' => 'activity priority',
            'duration' => 'activity duration',
            'location' => 'activity location',
            'tags' => 'activity tags',
            'custom_fields' => 'custom fields',
        ];
    }
}
