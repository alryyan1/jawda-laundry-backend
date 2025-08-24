<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\SettingsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            UserSeeder::class,
            CustomerTypeSeeder::class,      // Before CustomerSeeder
            CustomerSeeder::class,
            RestaurantTableSeeder::class,
            PermissionSeeder::class,

            // Product compositions (ingredients)
            ProductCompositionSeeder::class,
            // Sekka Shawarma menu (categories and product types)
            SekkaShawarmaMenuSeeder::class,
            SettingsSeeder::class,
            SekkaShawarmaSettingsSeeder::class,
            // Sekka Shawarma service offerings
            SekkaShawarmaServiceOfferingSeeder::class,
            // SupplierSeeder::class,
            // Add PricingRuleSeeder if you create one
        ]);
    }
}
