<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates a few specific, realistic suppliers and then uses a factory
     * to generate a larger set for testing purposes.
     */
    public function run(): void
    {
        $this->command->info('Seeding suppliers...');

        // --- Create a few specific suppliers for consistency in testing ---

        Supplier::firstOrCreate(
            ['name' => 'City Chemical Solutions'],
            [
                'contact_person' => 'Sarah Johnson',
                'phone' => '555-0101',
                'email' => 'sales@citychemical.com',
                'address' => '123 Industrial Park, Cleanville, ST 54321',
                'notes' => 'Primary vendor for detergents and stain removers.',
            ]
        );

        Supplier::firstOrCreate(
            ['name' => 'Packaging Pros Inc.'],
            [
                'contact_person' => 'Mike Brown',
                'phone' => '555-0102',
                'email' => 'orders@packagingpros.com',
                'address' => '456 Warehouse Way, Cleanville, ST 54321',
                'notes' => 'Source for poly bags, hangers, and tagging supplies.',
            ]
        );

        Supplier::firstOrCreate(
            ['name' => 'Office Supply Depot'],
            [
                'contact_person' => 'Linda Chen',
                'phone' => '555-0103',
                'email' => 'linda.c@officesupplyd.com',
                'address' => '789 Commerce Blvd, Cleanville, ST 54321',
                'notes' => 'For receipt paper rolls and office stationery.',
            ]
        );

        // --- Use the factory to create more random suppliers for volume testing ---
        
        // Only create factory data if the total number of suppliers is low.
        // This makes the seeder safe to re-run without creating hundreds of suppliers.
        if (Supplier::count() < 15) {
            $count = 15 - Supplier::count();
            Supplier::factory()->count($count)->create();
            $this->command->info("Created an additional {$count} random suppliers using the factory.");
        } else {
             $this->command->info('Supplier count is sufficient, skipping factory creation.');
        }

        $this->command->info('Supplier seeding completed.');
    }
}