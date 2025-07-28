<?php

namespace Database\Seeders\Laundry;

use Illuminate\Database\Seeder;
use App\Models\ServiceAction;

class LaundryServiceActionSeeder extends Seeder
{
    /**
     * Run the database seeds for laundry service actions.
     */
    public function run(): void
    {
        $this->command->info('Seeding laundry service actions...');

        $serviceActions = [
            [
                'name' => 'Ironing - كي',
                'description' => 'Professional ironing service for pressed and wrinkle-free garments. خدمة كي احترافية للملابس المكوية والخالية من التجاعيد.',
                'base_duration_minutes' => 15
            ],
            [
                'name' => 'Washing - غسيل',
                'description' => 'Standard washing service for regular cleaning of garments. خدمة غسيل قياسية للتنظيف العادي للملابس.',
                'base_duration_minutes' => 60
            ],
            [
                'name' => 'Dry Clean - غسيل جاف',
                'description' => 'Professional dry cleaning service for delicate and special fabrics. خدمة غسيل جاف احترافية للأقمشة الحساسة والخاصة.',
                'base_duration_minutes' => 120
            ]
        ];

        foreach ($serviceActions as $action) {
            ServiceAction::firstOrCreate(
                ['name' => $action['name']], 
                $action
            );
        }
        
        $this->command->info('Laundry service actions seeded successfully.');
    }
} 