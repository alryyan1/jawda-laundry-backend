<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'car_plate_number' => $this->car_plate_number,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'notes' => $this->notes,
            'user_id' => $this->user_id,
            'managedBy' => new UserResource($this->whenLoaded('managedBy')),
            'registered_date' => $this->registered_date,
            'total_orders' => $this->total_orders,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_default' => $this->is_default,
        ];
    }
}