<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Apparel', 'description' => 'Clothing items for men, women, and children.'],
            ['name' => 'Household Linens', 'description' => 'Bed sheets, towels, tablecloths, curtains.'],
            ['name' => 'Rugs & Carpets', 'description' => 'Area rugs, carpets, mats.'],
            ['name' => 'Outerwear', 'description' => 'Jackets, coats, and other outerwear.'],
            ['name' => 'Specialty Garments', 'description' => 'Wedding dresses, formal wear, delicate items.'],
        ];

        foreach ($categories as $category) {
            ProductCategory::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}