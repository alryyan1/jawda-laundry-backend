<?php

namespace Database\Seeders\Laundry;

use Illuminate\Database\Seeder;
use App\Models\ServiceOffering;
use App\Models\ProductType;
use App\Models\ServiceAction;

class LaundryServiceOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds for laundry service offerings.
     */
    public function run(): void
    {
        $this->command->info('Seeding laundry service offerings...');

        // Get all service actions
        $serviceActions = ServiceAction::all();
        
        // Get all product types
        $productTypes = ProductType::all();

        if ($serviceActions->isEmpty()) {
            $this->command->error('No service actions found. Please run LaundryServiceActionSeeder first.');
            return;
        }

        if ($productTypes->isEmpty()) {
            $this->command->error('No product types found. Please run LaundryProductTypeSeeder first.');
            return;
        }

        $createdCount = 0;

        foreach ($productTypes as $productType) {
            foreach ($serviceActions as $serviceAction) {
                // Create service offering for each product type and service action combination
                $serviceOffering = ServiceOffering::firstOrCreate(
                    [
                        'product_type_id' => $productType->id,
                        'service_action_id' => $serviceAction->id
                    ],
                    [
                        'product_type_id' => $productType->id,
                        'service_action_id' => $serviceAction->id,
                        'name_override' => null, // Use default display name
                        'description_override' => null, // Use default description
                        'default_price' => $this->getDefaultPrice($productType, $serviceAction),
                        'default_price_per_sq_meter' => $productType->is_dimension_based ? $this->getDefaultPricePerSqMeter($productType, $serviceAction) : null,
                        'applicable_unit' => $productType->is_dimension_based ? 'sq_meter' : 'piece',
                        'is_active' => true
                    ]
                );

                if ($serviceOffering->wasRecentlyCreated) {
                    $createdCount++;
                }
            }
        }

        $this->command->info("Laundry service offerings seeded successfully. Created {$createdCount} new offerings.");
    }

    /**
     * Get default price based on product type and service action
     * All prices are adjusted to be within the 0.1 to 0.8 range
     */
    private function getDefaultPrice(ProductType $productType, ServiceAction $serviceAction): float
    {
        // Base prices for different service actions (adjusted to 0.1-0.8 range)
        $basePrices = [
            'Ironing - كي' => 0.300,
            'Washing - غسيل' => 0.400,
            'Dry Clean - غسيل جاف' => 0.600
        ];

        $basePrice = $basePrices[$serviceAction->name] ?? 0.400;

        // Adjust price based on product type category
        $categoryName = $productType->productCategory->name ?? '';
        
        if (str_contains($categoryName, 'Special Items')) {
            return min(0.800, $basePrice * 1.3); // 30% premium for special items, capped at 0.8
        } elseif (str_contains($categoryName, 'Accessories')) {
            return max(0.100, $basePrice * 0.8); // 20% discount for accessories, minimum 0.1
        } elseif (str_contains($categoryName, 'Household Items')) {
            return min(0.800, $basePrice * 1.2); // 20% premium for household items, capped at 0.8
        } elseif (str_contains($categoryName, 'Carpets & Rugs')) {
            return min(0.800, $basePrice * 1.5); // 50% premium for carpets, capped at 0.8
        }

        return $basePrice; // Default for regular clothes
    }

    /**
     * Get default price per square meter for dimension-based items
     * All prices are adjusted to be within the 0.1 to 0.8 range
     */
    private function getDefaultPricePerSqMeter(ProductType $productType, ServiceAction $serviceAction): float
    {
        // Base prices per square meter for different service actions (adjusted to 0.1-0.8 range)
        $basePricesPerSqMeter = [
            'Ironing - كي' => 0.200,
            'Washing - غسيل' => 0.300,
            'Dry Clean - غسيل جاف' => 0.500
        ];

        $basePrice = $basePricesPerSqMeter[$serviceAction->name] ?? 0.300;

        // Adjust price based on product type category
        $categoryName = $productType->productCategory->name ?? '';
        
        if (str_contains($categoryName, 'Carpets & Rugs')) {
            return min(0.800, $basePrice * 1.4); // 40% premium for carpets, capped at 0.8
        }

        return $basePrice;
    }
} 