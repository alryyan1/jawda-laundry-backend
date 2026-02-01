<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\ProductType;
use App\Models\ProductCategory;

class ProductTypeRadhiSeeder extends Seeder
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

        // Fetch services from radhi database
        try {
            $services = DB::connection('radhi_temp')->table('services')->get();
        } catch (\Exception $e) {
            $this->command->error("Could not connect to 'radhi' database: " . $e->getMessage());
            return;
        }

        if ($services->isEmpty()) {
            $this->command->warn('No services found in radhi database.');
            return;
        }

        $count = 0;

        // Ensure we have a default category
        $defaultCategory = ProductCategory::first();
        if (!$defaultCategory) {
            $this->command->error('No Product Categories found. Please seed Product Categories first.');
            // return;
            //create category
            $defaultCategory = ProductCategory::create([
                'name' => 'Default Category',
                'description' => 'Default category for products',
            ]);
        }

        foreach ($services as $service) {

            // Map data
            $name = $service->service_name;
            // Assuming icon is just the filename, we might want to prefix it or leave it as is.
            // Based on existing data it seems to be just filename. 
            // In jawda system, it expects 'product_types/filename' usually or full path.
            // For now, I'll keep thefilename and let the frontend/accessor handle path, or prefix if needed.
            // NOTE: The previous inspection showed names like "Voq9Ajp9yQhdYQZY.png".
            // The constraint in ProductType is unique(['product_category_id', 'name']).

            $existing = ProductType::where('name', $name)
                ->where('product_category_id', $defaultCategory->id)
                ->first();

            if (!$existing) {
                ProductType::create([
                    'product_category_id' => $defaultCategory->id,
                    'name' => $name,
                    'description' => null, // Source has no description
                    'is_dimension_based' => false, // Default to item-based
                    'image_url' => 'product_types/' . $service->icon,
                ]);
                $count++;
            } else {
                // Update existing if needed, or skip. Let's just update image.
                $existing->update([
                    'image_url' => 'product_types/' . $service->icon
                ]);
            }
        }

        $this->command->info("Seeded {$count} new Product Types from radhi.services.");
    }
}
