<?php

namespace Database\Seeders\Laundry;

use Illuminate\Database\Seeder;
use App\Models\ServiceAction;

class LaundryServiceActionSeeder extends Seeder
{
    /**
     * Run the database seeds for restaurant meal variations.
     */
    public function run(): void
    {
        $this->command->info('Seeding restaurant meal variations...');

        $serviceActions = [
            [
                'name' => 'Small - صغير',
                'description' => 'Small portion size suitable for light meals or appetizers. حجم صغير مناسب للوجبات الخفيفة أو المقبلات.',
                'base_duration_minutes' => 10
            ],
            [
                'name' => 'Medium - متوسط',
                'description' => 'Standard portion size for regular meals. حجم قياسي للوجبات العادية.',
                'base_duration_minutes' => 15
            ],
            [
                'name' => 'Large - كبير',
                'description' => 'Large portion size for hearty meals or sharing. حجم كبير للوجبات الدسمة أو المشاركة.',
                'base_duration_minutes' => 20
            ]
        ];

        foreach ($serviceActions as $action) {
            ServiceAction::firstOrCreate(
                ['name' => $action['name']], 
                $action
            );
        }
        
        $this->command->info('Restaurant meal variations seeded successfully.');
    }
} 