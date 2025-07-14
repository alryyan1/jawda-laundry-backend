<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'service_offering_id' => $this->service_offering_id,
            'serviceOffering' => new ServiceOfferingResource($this->whenLoaded('serviceOffering')), // Key for details
            'product_description_custom' => $this->product_description_custom,
            'quantity' => (int) $this->quantity,
            'length_meters' => $this->whenNotNull($this->length_meters !== null ? (float) $this->length_meters : null),
            'width_meters' => $this->whenNotNull($this->width_meters !== null ? (float) $this->width_meters : null),
            'calculated_price_per_unit_item' => (float) $this->calculated_price_per_unit_item,
            'sub_total' => (float) $this->sub_total,
            'notes' => $this->notes,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'status' => $this->status,
            'picked_up_quantity' => (int) $this->picked_up_quantity,
        ];
    }
}