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
            NavigationItemSeeder::class,
            UserNavigationPermissionSeeder::class, // Set up navigation permissions for existing users
            // Product categories and types (must come before laundry seeder)
            ProductCategoriesTableSeeder::class,
            ProductTypesTableSeeder::class,
            SettingsSeeder::class,
            // Laundry seeders (creates service offerings)
            \Database\Seeders\Laundry\LaundrySeeder::class,
            // SupplierSeeder::class,
            // Add PricingRuleSeeder if you create one
        ]);
    }
}
