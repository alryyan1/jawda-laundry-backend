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
            PermissionSeeder::class,
            NavigationItemSeeder::class,
            UserNavigationPermissionSeeder::class, // Set up navigation permissions for existing users
            // Laundry seeders (creates categories first)
            \Database\Seeders\Laundry\LaundrySeeder::class,
            // Now seed product types after categories exist
            ProductTypesTableSeeder::class,
            // SupplierSeeder::class,
            // Add PricingRuleSeeder if you create one
        ]);
    }
}
