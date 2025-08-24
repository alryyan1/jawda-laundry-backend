<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductTypeCompositionResource extends JsonResource
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
            'product_type_id' => $this->product_type_id,
            'product_composition_id' => $this->product_composition_id,
            'name' => $this->productComposition?->name ?? 'Unknown',
            'description' => $this->description,
            'is_active' => $this->is_active,
            'product_composition' => $this->productComposition ? [
                'id' => $this->productComposition->id,
                'name' => $this->productComposition->name,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
