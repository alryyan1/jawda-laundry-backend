<?php

namespace App\Http\Resources;

use App\Http\Resources\ProductTypeResource;
use App\Http\Resources\ServiceActionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOfferingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_type_id' => $this->product_type_id,
            'productType' => new ProductTypeResource($this->whenLoaded('productType')),
            'service_action_id' => $this->service_action_id,
            'serviceAction' => new ServiceActionResource($this->whenLoaded('serviceAction')),
            'name_override' => $this->name_override,
            'display_name' => $this->display_name, // Accessor
            'description_override' => $this->description_override,
            'default_price' => (float) $this->default_price, // Cast to float
            'pricing_strategy' => $this->pricing_strategy,
            'default_price_per_sq_meter' => (float) $this->default_price_per_sq_meter, // Cast
            'applicable_unit' => $this->applicable_unit,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Optionally include pricing rules if needed in this context
            // 'pricing_rules' => PricingRuleResource::collection($this->whenLoaded('pricingRules')),
        ];
    }
}
