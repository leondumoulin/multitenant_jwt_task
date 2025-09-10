<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'duration' => $this->duration,
            'location' => $this->location,
            'tags' => $this->tags,
            'custom_fields' => $this->custom_fields,
            'user' => new UserResource($this->whenLoaded('user')),
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'deal' => new DealResource($this->whenLoaded('deal')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
