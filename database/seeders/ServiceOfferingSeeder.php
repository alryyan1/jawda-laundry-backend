<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceOffering;
use App\Models\ProductType;
use App\Models\ServiceAction;

class ServiceOfferingSeeder extends Seeder
{
    /**
     * Creates a 1-to-1 ServiceOffering for every ProductType, linking it
     * to its identically named ServiceAction and setting the price from the menu.
     */
    public function run(): void
    {
        $this->command->info('Creating 1-to-1 Service Offerings...');

        // A map of [ProductTypeID => Price] based on your PDF menu and INSERT statement.
        // This remains the source of truth for prices.
        $priceMap = [
            1 => 0.500, 2 => 0.400, 3 => 0.300, 4 => 0.200, 5 => 0.200, 6 => 0.300,
            7 => 0.300, 8 => 0.300, 9 => 0.300, 10 => 0.200, 11 => 0.800, 12 => 1.000,
            13 => 0.900, 14 => 0.900, 15 => 0.900, 16 => 0.900,
            // Prices for Al-Habab (Product Category 4)
            17 => 0.400, 18 => 0.500, 19 => 0.500, 21 => 0.500, 20 => 0.700,
            // Prices for Croissant (Product Category 5)
            22 => 0.300, 23 => 0.400, 27 => 0.500, 26 => 0.500, 28 => 0.500, 25 => 0.500,
            // Prices for Paratha (Product Category 6)
            29 => 0.300, 30 => 0.400, 34 => 0.400, 33 => 0.500, 35 => 0.500,
            // Prices for Chapati (Product Category 7)
            36 => 0.300, 37 => 0.400, 41 => 0.400, 40 => 0.500, 42 => 0.500, 43 => 0.500, 39 => 0.400,
            // Prices for Toast (Product Category 8)
            44 => 0.200, 45 => 0.400, 49 => 0.400, 48 => 0.400, 50 => 0.500, 51 => 0.500, 52 => 0.500, 47 => 0.400,
            // Pastries (Category 9)
            53 => 1.500, // Assuming large price for Luqaimat
            54 => 0.100,
            55 => 0.300, 56 => 0.300, 57 => 0.300, 58 => 0.300,
            // Other Meals (Category 10)
            59 => 2.900, 60 => 3.500, 61 => 1.500, 62 => 1.400, 63 => 1.500, 64 => 1.800, 65 => 1.500,
        ];
        // Note: The menu shows multiple prices for some items (e.g. Karak Tea). You must decide which price to use
        // or create separate ProductTypes like "Karak Tea - Small", "Karak Tea - Large" as discussed previously.
        // I have used a single price from the menu for these items for now.

        $productTypes = ProductType::all();
        $serviceActions = ServiceAction::all()->keyBy('name');

        foreach ($productTypes as $pt) {
            // Find the ServiceAction that has the exact same name as the ProductType
            $action = $serviceActions[$pt->name] ?? null;

            if ($action) {
                $price = $priceMap[$pt->id] ?? 0; // Default to 0 if not in our price map

                $offeringData = [
                    'is_active' => true,
                ];

                if ($pt->is_dimension_based) {
                    $offeringData['default_price_per_sq_meter'] = $price;
                } else {
                    $offeringData['default_price'] = $price;
                }

                ServiceOffering::updateOrCreate(
                    [
                        'product_type_id' => $pt->id,
                        'service_action_id' => $action->id,
                    ],
                    $offeringData
                );
            } else {
                $this->command->warn("Could not find a matching ServiceAction for ProductType: '{$pt->name}'");
            }
        }

        $this->command->info('Service Offerings created for all Product Types successfully.');
    }
}