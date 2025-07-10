<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceAction;

class ServiceActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder defines the core actions that can be performed on any laundry item,
     * with names provided in both English and Arabic.
     */
    public function run(): void
    {
        $this->command->info('Seeding service actions with dual language names...');

        $actions = [
            [
                'name' => 'Standard Wash & Fold - غسيل وطي عادي',
                'description' => 'Regular washing, drying, and folding.'
            ],
            [
                'name' => 'Delicate Wash - غسيل رقيق',
                'description' => 'Gentle cycle wash for delicate fabrics like silk or lace.'
            ],
            [
                'name' => 'Dry Cleaning - تنظيف جاف',
                'description' => 'Solvent-based cleaning for items not suitable for washing, such as suits and formal wear.'
            ],
            [
                'name' => 'Ironing / Pressing - كي / كبس',
                'description' => 'Professional ironing and pressing to remove wrinkles.'
            ],
            [
                'name' => 'Stain Removal Treatment - معالجة البقع',
                'description' => 'Specialized pre-treatment for tough stains before the main cleaning process.'
            ],
            [
                'name' => 'Carpet Deep Clean - تنظيف عميق للسجاد',
                'description' => 'Intensive washing and extraction cleaning for carpets and rugs.'
            ],
            [
                'name' => 'Quick Wash - غسيل سريع',
                'description' => 'Express washing service with a faster turnaround time.'
            ],
            [
                'name' => 'Quick Iron - كي سريع',
                'description' => 'Express ironing service.'
            ],
            [
                'name' => 'Alterations & Repairs - تعديلات وإصلاحات',
                'description' => 'Minor clothing repairs and alterations like fixing buttons or zippers.'
            ],
        ];

        foreach ($actions as $action) {
            // Use the English part of the name for the uniqueness check
            ServiceAction::firstOrCreate(
                ['name' => explode(' - ', $action['name'])[0]],
                $action
            );
        }

        $this->command->info('Service actions seeded successfully.');
    }
}