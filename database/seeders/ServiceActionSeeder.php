<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceAction;

class ServiceActionSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            ['name' => 'Standard Wash & Fold', 'description' => 'Regular washing, drying, and folding.'],
            ['name' => 'Delicate Wash', 'description' => 'Gentle cycle for delicate fabrics.'],
            ['name' => 'Dry Cleaning', 'description' => 'Solvent-based cleaning for items not suitable for washing.'],
            ['name' => 'Ironing / Pressing', 'description' => 'Professional ironing and pressing.'],
            ['name' => 'Stain Removal Treatment', 'description' => 'Specialized treatment for tough stains.'],
            ['name' => 'Carpet Deep Clean', 'description' => 'Intensive cleaning for carpets.'],
            ['name' => 'Quick Wash', 'description' => 'Express washing service.'],
            ['name' => 'Quick Iron', 'description' => 'Express ironing service.'],
            ['name' => 'Alterations & Repairs', 'description' => 'Minor clothing repairs and alterations.'],
        ];

        foreach ($actions as $action) {
            ServiceAction::firstOrCreate(['name' => $action['name']], $action);
        }
    }
}