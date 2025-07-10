<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceOffering;
use App\Models\ProductType;
use App\Models\ServiceAction;

class ServiceOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder connects Product Types with Service Actions to create your service menu.
     */
    public function run(): void
    {
        $this->command->info('Seeding service offerings...');

        // Fetch all relevant parent models once to be efficient.
        // We look them up by their English name part for consistency.
        $types = ProductType::all()->keyBy(function ($item) {
            return explode(' - ', $item->name)[0];
        });

        $actions = ServiceAction::all()->keyBy('name');

        // Define offerings as a structured array for readability and easy management
        $offeringsData = [
            // --- Apparel Offerings ---
            ['pt' => 'T-Shirt',             'sa' => 'Standard Wash & Fold', 'price' => 2.50],
            ['pt' => 'T-Shirt',             'sa' => 'Ironing / Pressing', 'price' => 1.50],
            ['pt' => 'T-Shirt',             'sa' => 'Quick Wash', 'price' => 3.50, 'is_active' => false], // Example of a currently disabled service
            ['pt' => 'Button-up Shirt',     'sa' => 'Standard Wash & Fold', 'price' => 3.00],
            ['pt' => 'Button-up Shirt',     'sa' => 'Dry Cleaning', 'price' => 5.00],
            ['pt' => 'Button-up Shirt',     'sa' => 'Ironing / Pressing', 'price' => 2.00],
            ['pt' => 'Trousers / Pants',    'sa' => 'Standard Wash & Fold', 'price' => 4.50],
            ['pt' => 'Trousers / Pants',    'sa' => 'Dry Cleaning', 'price' => 6.50],
            ['pt' => 'Trousers / Pants',    'sa' => 'Ironing / Pressing', 'price' => 3.50],
            ['pt' => 'Suit Jacket',         'sa' => 'Dry Cleaning', 'price' => 12.00],
            ['pt' => 'Suit Jacket',         'sa' => 'Ironing / Pressing', 'price' => 7.00],

            // --- Household Linens Offerings ---
            ['pt' => 'Queen Bed Sheet',     'sa' => 'Standard Wash & Fold', 'price' => 8.00],
            ['pt' => 'Duvet Cover',         'sa' => 'Standard Wash & Fold', 'price' => 18.00],
            ['pt' => 'Duvet Cover',         'sa' => 'Dry Cleaning', 'price' => 25.00],
            ['pt' => 'Bath Towel',          'sa' => 'Standard Wash & Fold', 'price' => 2.00],
            
            // --- Dimension-Based Offerings ---
            ['pt' => 'Curtains (per panel)','sa' => 'Dry Cleaning', 'price_sqm' => 4.50],
            ['pt' => 'Area Rug (Wool)',     'sa' => 'Carpet Deep Clean', 'price_sqm' => 8.50],
            ['pt' => 'Synthetic Carpet',    'sa' => 'Carpet Deep Clean', 'price_sqm' => 6.00],

            // --- A generic offering for bulk washing by weight ---
            ['pt' => 'Bulk Mixed Load',     'sa' => 'Standard Wash & Fold', 'price' => 5.50], // price per kg
        ];

        foreach ($offeringsData as $data) {
            // Check if both the product type and service action exist before creating the link
            if (isset($types[$data['pt']]) && isset($actions[$data['sa']])) {
                
                $productType = $types[$data['pt']];
                $serviceAction = $actions[$data['sa']];

                // Prepare the data for creation
                $offering = [
                    'product_type_id' => $productType->id,
                    'service_action_id' => $serviceAction->id,
                    'is_active' => $data['is_active'] ?? true, // Default to active
                ];

                // Add price based on the product type's pricing model (`is_dimension_based` flag)
                if ($productType->is_dimension_based) {
                    $offering['default_price_per_sq_meter'] = $data['price_sqm'] ?? 0;
                    $offering['default_price'] = null; // Ensure this is null for dimension-based
                } else {
                    $offering['default_price'] = $data['price'] ?? 0;
                    $offering['default_price_per_sq_meter'] = null;
                }

                // Use firstOrCreate to prevent creating duplicate offerings
                ServiceOffering::firstOrCreate(
                    [
                        'product_type_id' => $offering['product_type_id'],
                        'service_action_id' => $offering['service_action_id'],
                    ],
                    $offering
                );
            }
        }
        $this->command->info('Service offerings seeded successfully.');
    }
}