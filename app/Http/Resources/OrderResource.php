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
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'user_id' => $this->user_id,
            'staff_user' => new UserResource($this->whenLoaded('user')), // Assuming 'user' is the relationship name for staff
            'status' => $this->status,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'amount_due' => (float) $this->amount_due, // Accessor defined in Order model
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,
            'order_date' => $this->order_date ? $this->order_date->toIso8601String() : null,
            'due_date' => $this->due_date ? $this->due_date->toIso8601String() : null,
            'pickup_date' => $this->pickup_date ? $this->pickup_date->toIso8601String() : null,
            'delivery_address' => $this->delivery_address,
            'items' => OrderItemResource::collection($this->whenLoaded('items')), // Collection of OrderItemResource
            'payments' => PaymentResource::collection($this->whenLoaded('payments')), // Collection of PaymentResource
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}