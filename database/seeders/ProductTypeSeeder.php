<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductType;
use App\Models\ProductCategory;

class ProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        $apparelCat = ProductCategory::where('name', 'Apparel')->first();
        $linensCat = ProductCategory::where('name', 'Household Linens')->first();
        $rugsCat = ProductCategory::where('name', 'Rugs & Carpets')->first();

        $types = [];

        if ($apparelCat) {
            $types = array_merge($types, [
                ['product_category_id' => $apparelCat->id, 'name' => 'T-Shirt', 'is_dimension_based' => false],
                ['product_category_id' => $apparelCat->id, 'name' => 'Shirt (Button-up)', 'is_dimension_based' => false],
                ['product_category_id' => $apparelCat->id, 'name' => 'Trousers/Pants', 'is_dimension_based' => false],
                ['product_category_id' => $apparelCat->id, 'name' => 'Jeans', 'is_dimension_based' => false],
                ['product_category_id' => $apparelCat->id, 'name' => 'Suit Jacket', 'is_dimension_based' => false],
                ['product_category_id' => $apparelCat->id, 'name' => 'Dress', 'is_dimension_based' => false],
                ['product_category_id' => $apparelCat->id, 'name' => 'Skirt', 'is_dimension_based' => false],
            ]);
        }
        if ($linensCat) {
            $types = array_merge($types, [
                ['product_category_id' => $linensCat->id, 'name' => 'Bed Sheet (Single)', 'is_dimension_based' => false],
                ['product_category_id' => $linensCat->id, 'name' => 'Bed Sheet (Queen)', 'is_dimension_based' => false],
                ['product_category_id' => $linensCat->id, 'name' => 'Duvet Cover (Queen)', 'is_dimension_based' => false],
                ['product_category_id' => $linensCat->id, 'name' => 'Pillowcase', 'is_dimension_based' => false],
                ['product_category_id' => $linensCat->id, 'name' => 'Towel (Bath)', 'is_dimension_based' => false],
                ['product_category_id' => $linensCat->id, 'name' => 'Tablecloth (Medium)', 'is_dimension_based' => false],
                 ['product_category_id' => $linensCat->id, 'name' => 'Curtains (per panel)', 'is_dimension_based' => true],
            ]);
        }
        if ($rugsCat) {
            $types = array_merge($types, [
                ['product_category_id' => $rugsCat->id, 'name' => 'Area Rug (Wool)', 'is_dimension_based' => true],
                ['product_category_id' => $rugsCat->id, 'name' => 'Carpet (Synthetic)', 'is_dimension_based' => true],
            ]);
        }

        foreach ($types as $type) {
            ProductType::firstOrCreate(
                ['name' => $type['name'], 'product_category_id' => $type['product_category_id']],
                $type
            );
        }
    }
}