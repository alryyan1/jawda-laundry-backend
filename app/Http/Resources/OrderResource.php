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
            'daily_order_number' => $this->daily_order_number,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'table_id' => $this->table_id,
            'table' => new RestaurantTableResource($this->whenLoaded('table')),
            'dining_table_id' => $this->dining_table_id,
            'dining_table' => new DiningTableResource($this->whenLoaded('diningTable')),
            'user_id' => $this->user_id,
            'staff_user' => new UserResource($this->whenLoaded('user')), // Assuming 'user' is the relationship name for staff
            'status' => $this->status,
            'order_type' => $this->order_type,
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
               
            // أضف هذا الحقل. سيتم تضمينه فقط إذا كان موجودًا في كائن الطلب.
            'overdue_days' => $this->when(isset($this->overdue_days), (int) $this->overdue_days),
        ];
    }
}