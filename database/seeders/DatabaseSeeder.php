<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            ProductCategorySeeder::class,
            ProductTypeSeeder::class,
            PermissionSeeder::class,
            ServiceActionSeeder::class,
            ServiceOfferingSeeder::class,
            // ProductCategorySeeder::class,   // Before ProductTypeSeeder
            // ProductTypeSeeder::class,       // Before ServiceOfferingSeeder
            // ServiceActionSeeder::class,     // Before ServiceOfferingSeeder
            // ServiceOfferingSeeder::class,   // Before OrderSeeder
            // OrderSeeder::class,
            // PermissionSeeder::class, // Add this
            // SupplierSeeder::class,
            // Add PricingRuleSeeder if you create one
        ]);
    }
}
