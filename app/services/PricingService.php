<?php
// app/Services/PricingService.php
namespace App\Services;

use App\Models\ServiceOffering;
use App\Models\Customer;
use App\Models\PricingRule;

class PricingService
{
    public function calculatePrice(ServiceOffering $offering, Customer $customer = null, int $quantity = 1, float $length = null, float $width = null): array
    {
        $pricePerUnit = $offering->default_price;
        $strategy = $offering->pricing_strategy;
        // Get the applicable unit for pricing, falling back to the product type's base unit or 'item' if neither exists
        $unit = $offering->applicable_unit ?? $offering->productType?->base_measurement_unit ?? 'item';

        // 1. Check for customer-specific pricing rule
        if ($customer) {
            $rule = PricingRule::where('service_offering_id', $offering->id)
                ->where('customer_id', $customer->id)
                // ->where(date constraints for valid_from/to)
                ->first();
            if ($rule) {
                // Apply rule based on strategy
                if ($strategy === 'dimension_based' && $rule->price_per_sq_meter !== null) {
                    $pricePerUnit = $rule->price_per_sq_meter; // This is now price per sq meter
                } elseif ($rule->price !== null) {
                    $pricePerUnit = $rule->price;
                }
            }
            // 2. Else, check for customer_type specific pricing rule
            elseif ($customer->customerType) {
                $rule = PricingRule::where('service_offering_id', $offering->id)
                    ->where('customer_type_id', $customer->customer_type_id)
                    ->first();
                if ($rule) {
                    if ($strategy === 'dimension_based' && $rule->price_per_sq_meter !== null) {
                        $pricePerUnit = $rule->price_per_sq_meter;
                    } elseif ($rule->price !== null) {
                        $pricePerUnit = $rule->price;
                    }
                }
            }
        }

        // 3. Fallback to ServiceOffering defaults if no specific rule applied
        if ($strategy === 'dimension_based' && ($pricePerUnit === $offering->default_price || $pricePerUnit === null)) { // if no rule overrode
            $pricePerUnit = $offering->default_price_per_sq_meter ?: 0;
        }


        $finalItemPrice = 0;
        if ($strategy === 'fixed') {
            $finalItemPrice = $pricePerUnit * $quantity;
        } elseif ($strategy === 'dimension_based' && $length !== null && $width !== null) {
            $area = $length * $width;
            $finalItemPrice = $area * $pricePerUnit * $quantity; // quantity might be 1 for a single carpet
            $unit = 'sq_meter_total'; // for clarity
        } elseif ($strategy === 'per_unit_product') { // e.g. per kg
            // Assuming 'quantity' here represents the 'kg' or other unit measurement
            $finalItemPrice = $pricePerUnit * $quantity;
        } else { // Fallback if strategy is unclear or not met
            $finalItemPrice = $pricePerUnit * $quantity;
        }

        return [
            'calculated_price_per_unit_item' => $pricePerUnit, // This might be per item or per sq_meter based on strategy
            'sub_total' => $finalItemPrice,
            'applied_unit' => $unit, // Unit that price_per_unit refers to
            'strategy_applied' => $strategy
        ];
    }
}
