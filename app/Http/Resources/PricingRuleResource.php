<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingRuleResource extends JsonResource
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
            'service_offering_id' => $this->service_offering_id,
            'serviceOffering' => new ServiceOfferingResource($this->whenLoaded('serviceOffering')),
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'customer_type_id' => $this->customer_type_id,
            'customerType' => new CustomerTypeResource($this->whenLoaded('customerType')),
            'price' => $this->price !== null ? (float) $this->price : null,
            'price_per_sq_meter' => $this->price_per_sq_meter !== null ? (float) $this->price_per_sq_meter : null,
            'valid_from' => $this->valid_from ? $this->valid_from->toDateString() : null, // Y-m-d
            'valid_to' => $this->valid_to ? $this->valid_to->toDateString() : null,     // Y-m-d
            'min_quantity' => $this->min_quantity,
            'min_area_sq_meter' => $this->min_area_sq_meter !== null ? (float) $this->min_area_sq_meter : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}