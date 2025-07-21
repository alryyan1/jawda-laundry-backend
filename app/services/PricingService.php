<?php

namespace App\Services;

use App\Models\ServiceOffering;
use App\Models\Customer;
use App\Models\PricingRule;

class PricingService
{
    /**
     * Calculates the price for a given service offering and its context.
     *
     * This method determines the final price for an order item by checking for
     * specific pricing rules (customer-level then customer-type-level) and
     * falling back to the default prices defined on the ServiceOffering.
     * It also handles different calculation strategies based on whether the
     * product is priced by dimensions or a fixed/per-unit rate.
     *
     * @param ServiceOffering $offering The service being offered.
     * @param Customer|null $customer The customer for whom the price is being calculated.
     * @param int $quantity The quantity of the item.
     * @param float|null $length The length in meters (for dimension-based items).
     * @param float|null $width The width in meters (for dimension-based items).
     * @return array An array containing the calculated price per unit and the subtotal.
     */
    public function calculatePrice(
        ServiceOffering $offering,
        ?Customer $customer,
        int $quantity,
        ?float $length,
        ?float $width
    ): array
    {
        // Eager load necessary relationships if they haven't been loaded already.
        $offering->loadMissing(['productType', 'pricingRules']);
        if ($customer) {
            $customer->loadMissing('customerType.pricingRules');
        }

        $productType = $offering->productType;
        if (!$productType) {
            // Failsafe in case the relationship is broken
            return $this->defaultPriceResult();
        }

        $isDimensionBased = $productType->is_dimension_based;

        // --- Step 1: Determine the base price per unit from rules or defaults ---
        $pricePerUnit = $this->determinePricePerUnit($offering, $customer, $isDimensionBased);

        // --- Step 2: Calculate the subtotal based on the pricing model ---
        $subTotal = 0;
        // Determine the unit applicable to the price (e.g., 'item', 'kg', 'sq_meter')
        $appliedUnit = $offering->applicable_unit ?: ($isDimensionBased ? 'sq_meter' : 'item');

        if ($isDimensionBased) {
            if ($length > 0 && $width > 0) {
                $area = $length * $width;
                // Subtotal is area * price_per_sq_meter * quantity (quantity is usually 1 for carpets)
                $subTotal = $area * $pricePerUnit * $quantity;
            }
        } else {
            // For fixed or per_unit (like kg), subtotal is simply price * quantity
            $subTotal = $pricePerUnit * $quantity;
        }

        return [
            'calculated_price_per_unit_item' => $pricePerUnit,
            'sub_total' => round($subTotal, 3),
            'applied_unit' => $appliedUnit,
            'strategy_applied' => $isDimensionBased ? 'dimension_based' : 'fixed',
        ];
    }

    /**
     * Determines the correct price per unit by checking rules in order of precedence.
     * Precedence: Customer Rule > Customer Type Rule > Service Offering Default.
     *
     * @param ServiceOffering $offering
     * @param Customer|null $customer
     * @param bool $isDimensionBased
     * @return float
     */
    private function determinePricePerUnit(ServiceOffering $offering, ?Customer $customer, bool $isDimensionBased): float
    {
        // Set the default price from the ServiceOffering first
        $price = $isDimensionBased
            ? $offering->default_price_per_sq_meter
            : $offering->default_price;

        if (!$customer) {
            return (float) $price;
        }

        // 1. Check for a rule specific to this customer (highest priority)
        $customerRule = $offering->pricingRules
            ->where('customer_id', $customer->id)
            // You can add more complex rule checks here, e.g., for quantity tiers or date validity
            // ->where('valid_from', '<=', now())->where('valid_to', '>=', now())
            ->first();

        if ($customerRule) {
            $rulePrice = $isDimensionBased
                ? $customerRule->price_per_sq_meter
                : $customerRule->price;

            // If the rule has a valid price, use it. Otherwise, we'll continue to check customer type.
            if ($rulePrice !== null) {
                return (float) $rulePrice;
            }
        }

        // 2. If no customer-specific rule, check for a rule for the customer's type
        if ($customer->customerType) {
            $customerTypeRule = $offering->pricingRules
                ->where('customer_type_id', $customer->customer_type_id)
                ->first();

            if ($customerTypeRule) {
                $rulePrice = $isDimensionBased
                    ? $customerTypeRule->price_per_sq_meter
                    : $customerTypeRule->price;
                
                if ($rulePrice !== null) {
                    return (float) $rulePrice;
                }
            }
        }

        // 3. If no rules applied, return the initial default price from the offering
        return (float) $price;
    }

    /**
     * Provides a default result in case of an error or missing data.
     *
     * @return array
     */
    private function defaultPriceResult(): array
    {
        return [
            'calculated_price_per_unit_item' => 0.00,
            'sub_total' => 0.00,
            'applied_unit' => 'item',
            'strategy_applied' => 'fixed',
        ];
    }
}