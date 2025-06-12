<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductTypeResource extends JsonResource
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
            'base_measurement_unit' => $this->base_measurement_unit,
            'product_category_id' => $this->product_category_id,
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'is_active' => (bool) $this->is_active, // Assuming you add this field to the model & table
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'service_offerings_count' => $this->whenCounted('serviceOfferings'), // If you count offerings
        ];
    }
}