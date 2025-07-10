<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding product categories with dual language names...');

        $categories = [
            ['name' => 'Apparel - ملابس', 'description' => 'Clothing items for men, women, and children.'],
            ['name' => 'Household Linens - مفروشات منزلية', 'description' => 'Bed sheets, towels, tablecloths, curtains.'],
            ['name' => 'Rugs & Carpets - سجاد وموكيت', 'description' => 'Area rugs, carpets, mats.'],
            ['name' => 'Outerwear - ملابس خارجية', 'description' => 'Jackets, coats, and other outerwear.'],
            ['name' => 'Specialty Garments - ملابس خاصة', 'description' => 'Wedding dresses, formal wear, delicate items.'],
        ];

        foreach ($categories as $category) {
            // Use first part of the name for uniqueness check
            ProductCategory::firstOrCreate(['name' =>  $category['name']]);
        }
    }
}