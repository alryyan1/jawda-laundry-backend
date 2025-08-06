<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class CategorySequenceTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing categories with sequence data
        $categories = [
            [
                'name' => 'Carpets & Rugs - سجاد وبسط غسيل',
                'sequence_prefix' => 'Z',
                'sequence_enabled' => true,
                'current_sequence' => 0,
            ],
            [
                'name' => 'Clothes - ملابس غسيل',
                'sequence_prefix' => 'C',
                'sequence_enabled' => true,
                'current_sequence' => 0,
            ],
            [
                'name' => 'Special Items - قطع غسيل خاصة',
                'sequence_prefix' => 'S',
                'sequence_enabled' => true,
                'current_sequence' => 0,
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = ProductCategory::where('name', $categoryData['name'])->first();
            if ($category) {
                $category->update([
                    'sequence_prefix' => $categoryData['sequence_prefix'],
                    'sequence_enabled' => $categoryData['sequence_enabled'],
                    'current_sequence' => $categoryData['current_sequence'],
                ]);
            }
        }
    }
}
