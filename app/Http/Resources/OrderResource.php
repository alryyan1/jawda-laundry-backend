<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
   // app/Http/Resources/OrderResource.php
// ...
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'order_number' => $this->order_number,
        'customer' => new CustomerResource($this->whenLoaded('customer')),
        'staff_user' => new UserResource($this->whenLoaded('user')),
        'status' => $this->status,
        'total_amount' => (float) $this->total_amount,
        'paid_amount' => (float) $this->paid_amount,
        'notes' => $this->notes,
        'order_date' => $this->order_date->toIso8601String(),
        'due_date' => $this->due_date ? $this->due_date->toIso8601String() : null,
        'items' => OrderItemResource::collection($this->whenLoaded('items')),
        'created_at' => $this->created_at->toIso8601String(),
        'updated_at' => $this->updated_at->toIso8601String(),
    ];
}
}
