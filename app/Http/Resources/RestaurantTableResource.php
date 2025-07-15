<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantTableResource extends JsonResource
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
            'number' => $this->number,
            'capacity' => $this->capacity,
            'description' => $this->description,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'is_available' => $this->isAvailable(),
            'active_orders_count' => $this->whenCounted('activeOrders'),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
} 