<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            // 'service_id' => $this->service_id, // Can omit if full service is included
            'service' => new ServiceResource($this->whenLoaded('service')),
            'quantity' => $this->quantity,
            'price_at_order' => (float) $this->price_at_order,
            'sub_total' => (float) $this->sub_total,
        ];
    }
}