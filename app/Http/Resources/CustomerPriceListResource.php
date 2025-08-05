<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPriceListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'customer' => [
                'id' => $this['customer']['id'],
                'name' => $this['customer']['name'],
                'customer_type' => $this['customer']['customer_type'],
            ],
            'price_list' => $this['price_list'],
            'total_items' => $this['total_items'],
            'summary' => [
                'total_rules' => collect($this['price_list'])->whereNotNull('customer_specific_price')->count(),
                'active_rules' => collect($this['price_list'])->filter(function ($item) {
                    if (!$item['customer_specific_price']) return false;
                    $rule = $item['customer_specific_price'];
                    return (!$rule['valid_from'] || $rule['valid_from'] <= now()) &&
                           (!$rule['valid_to'] || $rule['valid_to'] >= now());
                })->count(),
                'expired_rules' => collect($this['price_list'])->filter(function ($item) {
                    if (!$item['customer_specific_price']) return false;
                    $rule = $item['customer_specific_price'];
                    return $rule['valid_to'] && $rule['valid_to'] < now();
                })->count(),
                'future_rules' => collect($this['price_list'])->filter(function ($item) {
                    if (!$item['customer_specific_price']) return false;
                    $rule = $item['customer_specific_price'];
                    return $rule['valid_from'] && $rule['valid_from'] > now();
                })->count(),
            ],
        ];
    }
} 