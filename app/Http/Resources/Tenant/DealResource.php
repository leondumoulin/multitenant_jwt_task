<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'value' => $this->value,
            'status' => $this->status,
            'probability' => $this->probability,
            'expected_close_date' => $this->expected_close_date?->toISOString(),
            'actual_close_date' => $this->actual_close_date?->toISOString(),
            'stage' => $this->stage,
            'source' => $this->source,
            'tags' => $this->tags,
            'custom_fields' => $this->custom_fields,
            'user' => new UserResource($this->whenLoaded('user')),
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
