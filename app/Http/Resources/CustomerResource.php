<?php
// app/Http/Resources/CustomerResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'user' => new UserResource($this->whenLoaded('user')), // Staff who created
            'registered_date' => $this->created_at->toIso8601String(),
            'total_orders' => $this->whenCounted('orders'), // If you use withCount('orders') in controller
            // 'orders_count' => $this->orders_count, // Alternative if withCount('orders') is used
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}