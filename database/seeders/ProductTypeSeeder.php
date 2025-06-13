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
                ['product_category_id' => $apparelCat->id, 'name' => 'T-Shirt', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $apparelCat->id, 'name' => 'Shirt (Button-up)', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $apparelCat->id, 'name' => 'Trousers/Pants', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $apparelCat->id, 'name' => 'Jeans', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $apparelCat->id, 'name' => 'Suit Jacket', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $apparelCat->id, 'name' => 'Dress', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $apparelCat->id, 'name' => 'Skirt', 'base_measurement_unit' => 'item'],
            ]);
        }
        if ($linensCat) {
            $types = array_merge($types, [
                ['product_category_id' => $linensCat->id, 'name' => 'Bed Sheet (Single)', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $linensCat->id, 'name' => 'Bed Sheet (Queen)', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $linensCat->id, 'name' => 'Duvet Cover (Queen)', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $linensCat->id, 'name' => 'Pillowcase', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $linensCat->id, 'name' => 'Towel (Bath)', 'base_measurement_unit' => 'item'],
                ['product_category_id' => $linensCat->id, 'name' => 'Tablecloth (Medium)', 'base_measurement_unit' => 'item'],
                 ['product_category_id' => $linensCat->id, 'name' => 'Curtains (per panel)', 'base_measurement_unit' => 'sq_meter'],
            ]);
        }
        if ($rugsCat) {
            $types = array_merge($types, [
                ['product_category_id' => $rugsCat->id, 'name' => 'Area Rug (Wool)', 'base_measurement_unit' => 'sq_meter'],
                ['product_category_id' => $rugsCat->id, 'name' => 'Carpet (Synthetic)', 'base_measurement_unit' => 'sq_meter'],
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