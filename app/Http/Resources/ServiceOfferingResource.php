<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOfferingResource extends JsonResource
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
            'product_type_id' => $this->product_type_id,
            'productType' => new ProductTypeResource($this->whenLoaded('productType')),
            'service_action_id' => $this->service_action_id,
            'serviceAction' => new ServiceActionResource($this->whenLoaded('serviceAction')),
            'name_override' => $this->name_override,
            'display_name' => $this->display_name, // Accessor from ServiceOffering model
            'description_override' => $this->description_override,
            'default_price' => $this->default_price !== null ? (float) $this->default_price : null,
            'pricing_strategy' => $this->pricing_strategy,
            'default_price_per_sq_meter' => $this->default_price_per_sq_meter !== null ? (float) $this->default_price_per_sq_meter : null,
            'applicable_unit' => $this->applicable_unit,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            // 'pricing_rules' => PricingRuleResource::collection($this->whenLoaded('pricingRules')), // If you need to show rules directly
        ];
    }
}