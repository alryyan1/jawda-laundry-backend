<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\ProductType;

class SekkaChaiMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main categories
        $categories = [
            [
                'name' => 'المشروبات الساخنة (Hot Drinks)',
                'description' => 'Hot beverages and teas',
            ],
            [
                'name' => 'المشروبات الباردة (Cold Drinks)',
                'description' => 'Cold beverages and refreshments',
            ],
            [
                'name' => 'اللقيمات (Luqaimat)',
                'description' => 'Sweet dumplings with various toppings',
            ],
            [
                'name' => 'مندازي (Mandazi)',
                'description' => 'Traditional fried bread with various fillings',
            ],
            [
                'name' => 'فطاير صلالة (Fatair Salalah)',
                'description' => 'Omani-style pastries with various fillings',
            ],
            [
                'name' => 'خبز رقاق (Khubz Ruqaq)',
                'description' => 'Thin bread with various fillings',
            ],
            [
                'name' => 'توست (Toast)',
                'description' => 'Toasted bread with various toppings',
            ],
            [
                'name' => 'بوكس سكة (Box Sekka)',
                'description' => 'Special combo boxes',
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = ProductCategory::create($categoryData);
            
            // Create product types for each category
            $this->createProductTypesForCategory($category);
        }
    }

    private function createProductTypesForCategory(ProductCategory $category)
    {
        $productTypes = [];

        switch ($category->name) {
            case 'المشروبات الساخنة (Hot Drinks)':
                $productTypes = [
                    ['name' => 'شاي مدخن (Smoked Tea)', 'description' => 'Traditional smoked tea'],
                    ['name' => 'شاي خدري (Khadri Tea)', 'description' => 'Khadri style tea'],
                    ['name' => 'شاي كرك (Karak Tea)', 'description' => 'Traditional Karak tea'],
                    ['name' => 'كرك زعفران (Saffron Karak)', 'description' => 'Karak tea with saffron'],
                    ['name' => 'شاي احمر (Red Tea)', 'description' => 'Red tea'],
                    ['name' => 'كابتشينو (Cappuccino)', 'description' => 'Italian cappuccino'],
                    ['name' => 'نسكافية (Nescafe)', 'description' => 'Instant coffee'],
                ];
                break;

            case 'المشروبات الباردة (Cold Drinks)':
                $productTypes = [
                    ['name' => 'كركدية (Karkadeh)', 'description' => 'Hibiscus drink'],
                    ['name' => 'ماي (Water)', 'description' => 'Mineral water'],
                ];
                break;

            case 'اللقيمات (Luqaimat)':
                $productTypes = [
                    ['name' => 'صوص سكه الخاص (Special Sekka Sauce)', 'description' => 'Luqaimat with special Sekka sauce'],
                    ['name' => 'عسل (Honey)', 'description' => 'Luqaimat with honey'],
                    ['name' => 'حليب مكثف (Condensed Milk)', 'description' => 'Luqaimat with condensed milk'],
                    ['name' => 'لوتس (Lotus)', 'description' => 'Luqaimat with lotus spread'],
                    ['name' => 'بستاشيو (Pistachio)', 'description' => 'Luqaimat with pistachio'],
                ];
                break;

            case 'مندازي (Mandazi)':
                $productTypes = [
                    ['name' => 'دنجو (Dango)', 'description' => 'Mandazi with chickpeas'],
                    ['name' => 'جبن بيض (Cheese Egg)', 'description' => 'Mandazi with cheese and egg'],
                    ['name' => 'جبن بطاطس عمان (Oman Chips Cheese)', 'description' => 'Mandazi with cheese and Oman chips'],
                    ['name' => 'جبن (Cheese)', 'description' => 'Mandazi with cheese'],
                    ['name' => 'مندازي ساده (Plain Mandazi)', 'description' => 'Plain mandazi without filling'],
                    ['name' => 'عسل (Honey)', 'description' => 'Mandazi with honey'],
                ];
                break;

            case 'فطاير صلالة (Fatair Salalah)':
                $productTypes = [
                    ['name' => 'جبن عسل (Cheese Honey)', 'description' => 'Fatair with cheese and honey'],
                    ['name' => 'جبن بطاطس (Cheese Potato)', 'description' => 'Fatair with cheese and potato'],
                    ['name' => 'جبن زعتر (Cheese Thyme)', 'description' => 'Fatair with cheese and thyme'],
                    ['name' => 'جبن دجاج (Cheese Chicken)', 'description' => 'Fatair with cheese and chicken'],
                    ['name' => 'جبن بيض (Cheese Egg)', 'description' => 'Fatair with cheese and egg'],
                    ['name' => 'جبن تونه (Cheese Tuna)', 'description' => 'Fatair with cheese and tuna'],
                    ['name' => 'جبن نقانق (Cheese Sausage)', 'description' => 'Fatair with cheese and sausage'],
                ];
                break;

            case 'خبز رقاق (Khubz Ruqaq)':
                $productTypes = [
                    ['name' => 'جبن (Cheese)', 'description' => 'Khubz ruqaq with cheese'],
                    ['name' => 'جبن عسل (Cheese Honey)', 'description' => 'Khubz ruqaq with cheese and honey'],
                    ['name' => 'جبن بيض (Cheese Egg)', 'description' => 'Khubz ruqaq with cheese and egg'],
                    ['name' => 'جبن بطاطس (Cheese Potato)', 'description' => 'Khubz ruqaq with cheese and potato'],
                    ['name' => 'جبن نقانق (Cheese Sausage)', 'description' => 'Khubz ruqaq with cheese and sausage'],
                    ['name' => 'نوتيلا (Nutella)', 'description' => 'Khubz ruqaq with Nutella'],
                ];
                break;

            case 'توست (Toast)':
                $productTypes = [
                    ['name' => 'جبن (Cheese)', 'description' => 'Toast with cheese'],
                    ['name' => 'جبن بطاطس عمان (Oman Chips Cheese)', 'description' => 'Toast with cheese and Oman chips'],
                    ['name' => 'جبن نقانق (Cheese Sausage)', 'description' => 'Toast with cheese and sausage'],
                    ['name' => 'جبن بيض (Cheese Egg)', 'description' => 'Toast with cheese and egg'],
                    ['name' => 'جبن دجاج (Cheese Chicken)', 'description' => 'Toast with cheese and chicken'],
                ];
                break;

            case 'بوكس سكة (Box Sekka)':
                $productTypes = [
                    ['name' => 'بوكس سكة (Box Sekka)', 'description' => 'Special combo: 2 Karak + 6 types of Mandazi (Plain, Cheese Egg, Cheese, Honey, Oman Chips, Dango)'],
                ];
                break;
        }

        foreach ($productTypes as $productTypeData) {
            ProductType::create([
                'product_category_id' => $category->id,
                'name' => $productTypeData['name'],
                'description' => $productTypeData['description'],
                'is_dimension_based' => false,
            ]);
        }
    }
}
