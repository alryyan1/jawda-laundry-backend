<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\ProductType;
use App\Models\ProductCategory;

class ProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding product types with dual language names...');
        
        // Helper to find category by English name
        $findCat = fn($en_name) => ProductCategory::where('name', 'LIKE', $en_name . '%')->first();

        $apparelCat = $findCat('Apparel');
        $linensCat = $findCat('Household Linens');
        $rugsCat = $findCat('Rugs & Carpets');

        $types = [];

        if ($apparelCat) {
            $types = array_merge($types, [
                ['product_category_id' => $apparelCat->id, 'name' => 'T-Shirt - تي شيرت', 'is_dimension_based' => false],
                ['product_category_id' => $apparelCat->id, 'name' => 'Button-up Shirt - قميص رسمي', 'is_dimension_based' => false],
                ['product_category_id' => $apparelCat->id, 'name' => 'Trousers / Pants - بنطلون', 'is_dimension_based' => false],
                ['product_category_id' => $apparelCat->id, 'name' => 'Suit Jacket - جاكيت بدلة', 'is_dimension_based' => false],
            ]);
        }
        if ($linensCat) {
            $types = array_merge($types, [
                ['product_category_id' => $linensCat->id, 'name' => 'Queen Bed Sheet - ملاءة سرير (كوين)', 'is_dimension_based' => false],
                ['product_category_id' => $linensCat->id, 'name' => 'Duvet Cover - غطاء لحاف', 'is_dimension_based' => false],
                ['product_category_id' => $linensCat->id, 'name' => 'Bath Towel - منشفة حمام', 'is_dimension_based' => false],
                ['product_category_id' => $linensCat->id, 'name' => 'Curtains (per panel) - ستائر (للقطعة)', 'is_dimension_based' => true],
            ]);
        }
        if ($rugsCat) {
            $types = array_merge($types, [
                ['product_category_id' => $rugsCat->id, 'name' => 'Wool Area Rug - سجادة صوف', 'is_dimension_based' => true],
                ['product_category_id' => $rugsCat->id, 'name' => 'Synthetic Carpet - سجاد صناعي', 'is_dimension_based' => true],
            ]);
        }

        foreach ($types as $type) {
            ProductType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}