<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_url ? asset('storage/' . $this->image_url) : null,
            'sequence_prefix' => $this->sequence_prefix,
            'sequence_enabled' => $this->sequence_enabled,
            'current_sequence' => $this->current_sequence,
            'next_sequence' => $this->getNextSequence(),
            'product_types_count' => $this->whenCounted('productTypes'),
            // 'product_types' => ProductTypeResource::collection($this->whenLoaded('productTypes')), // If needed
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}