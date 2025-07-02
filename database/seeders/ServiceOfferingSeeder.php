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
     */
    public function run(): void
    {
        $this->command->info('Seeding service offerings...');

        // Fetch all relevant actions and types once to be efficient
        $actions = ServiceAction::pluck('id', 'name');
        $types = ProductType::pluck('id', 'name');

        // Define offerings as a structured array
        $offeringsData = [
            // Apparel - T-Shirt
            ['pt' => 'T-Shirt', 'sa' => 'Standard Wash & Fold', 'price' => 2.50],
            ['pt' => 'T-Shirt', 'sa' => 'Ironing / Pressing', 'price' => 1.50],
            ['pt' => 'T-Shirt', 'sa' => 'Quick Wash', 'price' => 3.50, 'is_active' => false], // Example of an inactive service

            // Apparel - Suit Jacket
            ['pt' => 'Suit Jacket', 'sa' => 'Dry Cleaning', 'price' => 12.00],
            ['pt' => 'Suit Jacket', 'sa' => 'Ironing / Pressing', 'price' => 7.00],

            // Apparel - Trousers/Pants
            ['pt' => 'Trousers/Pants', 'sa' => 'Standard Wash & Fold', 'price' => 4.00],
            ['pt' => 'Trousers/Pants', 'sa' => 'Dry Cleaning', 'price' => 6.00],
            ['pt' => 'Trousers/Pants', 'sa' => 'Ironing / Pressing', 'price' => 3.00],

            // Linens
            ['pt' => 'Duvet Cover (Queen)', 'sa' => 'Standard Wash & Fold', 'price' => 15.00],
            ['pt' => 'Duvet Cover (Queen)', 'sa' => 'Dry Cleaning', 'price' => 25.00],
            ['pt' => 'Curtains (per panel)', 'sa' => 'Dry Cleaning', 'price_sqm' => 4.50],

            // Rugs & Carpets
            ['pt' => 'Area Rug (Wool)', 'sa' => 'Carpet Deep Clean', 'price_sqm' => 8.50],
            ['pt' => 'Carpet (Synthetic)', 'sa' => 'Carpet Deep Clean', 'price_sqm' => 6.00],
        ];

        foreach ($offeringsData as $data) {
            // Check if both the product type and service action exist
            if (isset($types[$data['pt']]) && isset($actions[$data['sa']])) {
                $productType = ProductType::find($types[$data['pt']]);

                // Create the offering data array
                $offering = [
                    'product_type_id' => $productType->id,
                    'service_action_id' => $actions[$data['sa']],
                    'is_active' => $data['is_active'] ?? true, // Default to active
                ];

                // Add price based on the product type's pricing model
                if ($productType->is_dimension_based) {
                    $offering['default_price_per_sq_meter'] = $data['price_sqm'] ?? 0;
                } else {
                    $offering['default_price'] = $data['price'] ?? 0;
                }

                // Use firstOrCreate to prevent duplicates
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