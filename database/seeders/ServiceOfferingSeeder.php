<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use App\Models\ServiceOffering;
use App\Models\ProductType;
use App\Models\ServiceAction;

class ServiceOfferingSeeder extends Seeder
{
    public function run(): void
    {
        $offerings = [];

        // T-Shirt Offerings
        $tshirt = ProductType::where('name', 'T-Shirt')->first();
        $washFoldAction = ServiceAction::where('name', 'Standard Wash & Fold')->first();
        $ironAction = ServiceAction::where('name', 'Ironing / Pressing')->first();
        $quickWashAction = ServiceAction::where('name', 'Quick Wash')->first();

        if ($tshirt && $washFoldAction) {
            $offerings[] = ['product_type_id' => $tshirt->id, 'service_action_id' => $washFoldAction->id, 'default_price' => 2.50, 'pricing_strategy' => 'fixed', 'applicable_unit' => 'item', 'is_active' => true];
        }
        if ($tshirt && $ironAction) {
            $offerings[] = ['product_type_id' => $tshirt->id, 'service_action_id' => $ironAction->id, 'default_price' => 1.50, 'pricing_strategy' => 'fixed', 'applicable_unit' => 'item', 'is_active' => true];
        }
        if ($tshirt && $quickWashAction) {
            $offerings[] = ['product_type_id' => $tshirt->id, 'service_action_id' => $quickWashAction->id, 'default_price' => 3.50, 'pricing_strategy' => 'fixed', 'applicable_unit' => 'item', 'is_active' => true];
        }


        // Suit Jacket Offerings
        $suitJacket = ProductType::where('name', 'Suit Jacket')->first();
        $dryCleanAction = ServiceAction::where('name', 'Dry Cleaning')->first();
        if ($suitJacket && $dryCleanAction) {
            $offerings[] = ['product_type_id' => $suitJacket->id, 'service_action_id' => $dryCleanAction->id, 'default_price' => 12.00, 'pricing_strategy' => 'fixed', 'applicable_unit' => 'item', 'is_active' => true];
        }
        if ($suitJacket && $ironAction) { // Suit jacket pressing
             $offerings[] = ['product_type_id' => $suitJacket->id, 'service_action_id' => $ironAction->id, 'default_price' => 7.00, 'pricing_strategy' => 'fixed', 'applicable_unit' => 'item', 'is_active' => true];
        }


        // Carpet Offerings
        $woolCarpet = ProductType::where('name', 'Area Rug (Wool)')->first(); // base_measurement_unit is 'sq_meter'
        $carpetCleanAction = ServiceAction::where('name', 'Carpet Deep Clean')->first();
        if ($woolCarpet && $carpetCleanAction) {
            $offerings[] = [
                'product_type_id' => $woolCarpet->id,
                'service_action_id' => $carpetCleanAction->id,
                'pricing_strategy' => 'dimension_based',
                'default_price_per_sq_meter' => 8.00, // Price per square meter
                'applicable_unit' => 'sq_meter', // Explicitly set for clarity
                'is_active' => true
            ];
        }
        
        // Bulk Wash
        $mixedLoadWashProduct = ProductType::firstOrCreate( // Create a generic product type for bulk if needed
            ['name' => 'Bulk Mixed Load', 'product_category_id' => ProductCategory::where('name', 'Apparel')->first()?->id],
            ['base_measurement_unit' => 'kg']
        );
        if ($mixedLoadWashProduct && $washFoldAction) {
             $offerings[] = [
                'product_type_id' => $mixedLoadWashProduct->id,
                'service_action_id' => $washFoldAction->id,
                'pricing_strategy' => 'per_unit_product', // per kg
                'default_price' => 5.00, // price per kg
                'applicable_unit' => 'kg',
                'is_active' => true
            ];
        }


        foreach ($offerings as $offeringData) {
            ServiceOffering::firstOrCreate(
                ['product_type_id' => $offeringData['product_type_id'], 'service_action_id' => $offeringData['service_action_id']],
                $offeringData
            );
        }
    }
}