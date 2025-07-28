<?php

namespace Database\Seeders\Laundry;

use Illuminate\Database\Seeder;

class LaundrySeeder extends Seeder
{
    /**
     * Run the laundry database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting laundry seeding process...');
        
        // Run category seeder first
        $this->call([
            LaundryCategorySeeder::class,
        ]);
        
        // Run product type seeder after categories are created
        $this->call([
            LaundryProductTypeSeeder::class,
        ]);
        
        // Run service action seeder
        $this->call([
            LaundryServiceActionSeeder::class,
        ]);
        
        // Run service offering seeder after both product types and service actions are created
        $this->call([
            LaundryServiceOfferingSeeder::class,
        ]);
        
        $this->command->info('Laundry seeding completed successfully.');
    }
} 