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
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'notes' => $this->notes,
            'customer_type_id' => $this->customer_type_id,
            'customerType' => new CustomerTypeResource($this->whenLoaded('customerType')),
            'user_id' => $this->user_id, // Staff who created/manages
            'managedBy' => new UserResource($this->whenLoaded('managedBy')), // Relationship name from Customer model
            'registered_date' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'total_orders' => $this->whenCounted('orders'),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'is_default' => $this->is_default,
            // You can add a link to their orders if needed:
            // 'orders_link' => $this->when(Auth::check(), route('api.orders.index', ['customer_id' => $this->id])),
        ];
    }
}