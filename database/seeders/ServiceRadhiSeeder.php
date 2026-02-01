<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\ServiceAction;
use App\Models\ServiceOffering;
use App\Models\ProductType;

class ServiceRadhiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the radhi database connection dynamically
        Config::set('database.connections.radhi_temp', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => 'radhi', // Source database
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        $this->command->info('Fetching data from radhi database...');

        try {
            $radhiServiceTypes = DB::connection('radhi_temp')->table('service_types')->get();
            $radhiServices = DB::connection('radhi_temp')->table('services')->get(); // valid product types keys
            $radhiServiceDetails = DB::connection('radhi_temp')->table('service_details')->get();
        } catch (\Exception $e) {
            $this->command->error("Could not connect to 'radhi' database: " . $e->getMessage());
            return;
        }

        // --- Step 1: Seed Service Actions ---
        $this->command->info('Seeding Service Actions...');

        $radhiTypeMap = []; // radhi_id => name
        foreach ($radhiServiceTypes as $type) {
            $name = $type->service_type_name;
            $radhiTypeMap[$type->id] = $name;

            // Check if exists
            $action = ServiceAction::where('name', $name)->first();
            if (!$action) {
                ServiceAction::create([
                    'name' => $name,
                    'description' => null,
                    'base_duration_minutes' => 60, // Default duration
                ]);
            }
        }

        // Refresh Jawda Actions Map (Name => ID)
        $jawdaActions = ServiceAction::all()->pluck('id', 'name');

        // --- Step 2: Seed Service Offerings ---
        $this->command->info('Seeding Service Offerings...');

        // Map Radhi Services (Product Types) ID => Name
        $radhiServiceMap = [];
        foreach ($radhiServices as $service) {
            $radhiServiceMap[$service->id] = $service->service_name;
        }

        // Map Jawda Product Types (Name => ID)
        // We assume ProductTypeRadhiSeeder has run, so names match.
        // However, names might be duplicated across categories? 
        // In our case we put everything in category 1, so name should be unique for now combined with category.
        // We will just lookup by name.
        $jawdaProductTypes = ProductType::all()->pluck('id', 'name');

        $count = 0;
        foreach ($radhiServiceDetails as $detail) {

            // Resolve Product Type
            $productName = $radhiServiceMap[$detail->service_id] ?? null;
            if (!$productName) continue; // Radhi product not found?

            $jawdaProductId = $jawdaProductTypes[$productName] ?? null;
            if (!$jawdaProductId) {
                // Warning? Or maybe fetching fresh might help if seeder ran recently?
                // We fetched all at start.
                continue;
            }

            // Resolve Service Action
            $actionName = $radhiTypeMap[$detail->service_type_id] ?? null;
            if (!$actionName) continue;

            $jawdaActionId = $jawdaActions[$actionName] ?? null;
            if (!$jawdaActionId) continue;

            // Create Offering
            // Check existence
            $exists = ServiceOffering::where('product_type_id', $jawdaProductId)
                ->where('service_action_id', $jawdaActionId)
                ->exists();

            if (!$exists) {
                ServiceOffering::create([
                    'product_type_id' => $jawdaProductId,
                    'service_action_id' => $jawdaActionId,
                    'default_price' => $detail->service_price,
                    // 'pricing_strategy' removed as per migration
                    'is_active' => true,
                ]);
                $count++;
            } else {
                // Update price?
                ServiceOffering::where('product_type_id', $jawdaProductId)
                    ->where('service_action_id', $jawdaActionId)
                    ->update(['default_price' => $detail->service_price]);
            }
        }

        $this->command->info("Seeded {$count} new Service Offerings.");
    }
}
