<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\ProductType;

class SekkaShawarmaMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SEKKA Shawarma categories with products (product types)
        $menu = [
            [
                'name' => 'بوكسات (Boxes)',
                'description' => 'Combo boxes',
                'types' => [
                    'بوكس سكة لحم',
                    'بوكس سكة دجاج',
                    'عراقي بوكس',
                ],
            ],
            [
                'name' => 'سندويشات (Sandwiches)',
                'description' => 'Shawarma and grills sandwiches',
                'types' => [
                    'تكة',
                    'كبدة',
                    'كباب',
                    'فلافل',
                ],
            ],
            [
                'name' => 'فرايز (Fries)',
                'description' => 'Loaded fries',
                'types' => [
                    'فرايز لحم',
                    'فرايز دجاج',
                ],
            ],
            [
                'name' => 'عراقي (Iraqi Style)',
                'description' => 'Iraqi style sandwiches',
                'types' => [
                    'عراقي دجاج',
                    'عراقي لحم',
                ],
            ],
            [
                'name' => 'صاروق (Sarouq)',
                'description' => 'Sarouq sandwiches',
                'types' => [
                    'صاروق دجاج',
                    'صاروق لحم',
                ],
            ],
            [
                'name' => 'شبس (Chips)',
                'description' => 'Special chips',
                'types' => [
                    'شبس سكة الخاص',
                ],
            ],
            [
                'name' => 'مشروبات (Drinks)',
                'description' => 'Beverages',
                'types' => [
                    'لبن بالنعناع',
                    'ليمون نعاع',
                    'ليمون فراولة',
                    'مانجو باشن',
                    'كينزا',
                ],
            ],
            [
                'name' => 'وجبات التوفير (Value Meals)',
                'description' => 'Meal + chips + drink combos',
                'types' => [
                    'فلافل (وجبة توفير)',
                    'كبدة (وجبة توفير)',
                    'تكة (وجبة توفير)',
                    'كباب (وجبة توفير)',
                    'عراقي دجاج (وجبة توفير)',
                    'عراقي لحم (وجبة توفير)',
                ],
            ],
        ];

        foreach ($menu as $categoryData) {
            $category = ProductCategory::firstOrCreate([
                'name' => $categoryData['name'],
            ], [
                'description' => $categoryData['description'] ?? null,
                'sequence_enabled' => false,
                'current_sequence' => 0,
            ]);

            foreach ($categoryData['types'] as $typeName) {
                ProductType::firstOrCreate([
                    'product_category_id' => $category->id,
                    'name' => $typeName,
                ], [
                    'description' => null,
                    'is_dimension_based' => false,
                ]);
            }
        }
    }
}


