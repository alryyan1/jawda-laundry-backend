<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceAction;
use App\Models\ProductType;

class ServiceActionSeeder extends Seeder
{
    /**
     * Creates a corresponding ServiceAction for every existing ProductType.
     */
    public function run(): void
    {
        $this->command->info('Creating a 1-to-1 Service Action for each Product Type...');

        // Get all product types that have already been seeded
        $productTypes = ProductType::all();

        if ($productTypes->isEmpty()) {
            $this->command->error('No Product Types found. Please run ProductTypeSeeder first.');
            return;
        }

        foreach ($productTypes as $productType) {
            // Use firstOrCreate to prevent duplicates and be re-runnable
            ServiceAction::firstOrCreate(
                ['name' => $productType->name], // The name is the unique key
                ['description' => "Action to prepare or serve '{$productType->name}'."]
            );
        }

        $this->command->info('Service Actions created for all Product Types successfully.');
    }
}