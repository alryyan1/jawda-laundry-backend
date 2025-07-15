<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds based on the provided cafe menu.
     */
    public function run(): void
    {
        $this->command->info('Seeding product categories from menu...');

        $categories = [
            ['name' => 'Hot Drinks - مشروبات ساخنة', 'description' => 'A selection of classic hot beverages.'],
            ['name' => 'Specialty Coffee - قهوة مختصة', 'description' => 'Premium coffee selection.'],
            ['name' => 'Salalah Pancakes - فطائر صلالة', 'description' => 'Traditional pancakes from Salalah.'],
            ['name' => 'Al-Habab Bread - الجباب', 'description' => 'A type of traditional bread.'], // Assuming "Al-Habab" is the correct name, as it's common.
            ['name' => 'Croissant - كرواسون', 'description' => 'Flaky croissants with various fillings.'],
            ['name' => 'Paratha Bread - خبز البراتا', 'description' => 'Layered flatbread with fillings.'],
            ['name' => 'Chapati Bread - خبز الجباتي', 'description' => 'Traditional unleavened flatbread.'],
            ['name' => 'Toast - خبز التوست', 'description' => 'Toasted bread with various toppings.'],
            ['name' => 'Pastries - المعجنات', 'description' => 'Sweet and savory pastries.'],
            ['name' => 'Other Meals - وجبات اخرى', 'description' => 'Main breakfast and other meals.'],
        ];

        foreach ($categories as $category) {
            ProductCategory::firstOrCreate(['name' => explode(' - ', $category['name'])[0]], $category);
        }
        
        $this->command->info('Product categories seeded successfully.');
    }
}