<?php

namespace Database\Seeders\Laundry;

use Illuminate\Database\Seeder;
use App\Models\ProductType;
use App\Models\ProductCategory;

class LaundryProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds for laundry product types.
     */
    public function run(): void
    {
        $this->command->info('Seeding laundry product types...');
        
        // Helper function to find a category by its English name part
        $findCat = fn($en_name) => ProductCategory::where('name', 'LIKE', $en_name . '%')->first();

        // --- Laundry Clothes Category ---
        $clothesCat = $findCat('Clothes');
        if ($clothesCat) {
            $this->createTypesForCategory($clothesCat, [
                ['name' => 'Shirts - قمصان', 'description' => 'Standard shirts for everyday wear', 'is_dimension_based' => false],
                ['name' => 'Trousers / Pants - بناطيل', 'description' => 'Pants and trousers', 'is_dimension_based' => false],
                ['name' => 'T-Shirts - تي شيرت', 'description' => 'Casual t-shirts', 'is_dimension_based' => false],
                ['name' => 'Dresses - فساتين', 'description' => 'Various types of dresses', 'is_dimension_based' => false],
                ['name' => 'Skirts - تنانير', 'description' => 'Different styles of skirts', 'is_dimension_based' => false],
                ['name' => 'Jackets / Coats - جواكت ومعاطف', 'description' => 'Outerwear jackets and coats', 'is_dimension_based' => false],
                ['name' => 'Abayas / Thobes - عباءات وثياب', 'description' => 'Traditional Arabic clothing', 'is_dimension_based' => false],
                ['name' => 'Suits - بدلات', 'description' => 'Formal suits', 'is_dimension_based' => false],
                ['name' => 'Underwear - ملابس داخلية', 'description' => 'Underwear items', 'is_dimension_based' => false],
                ['name' => 'Uniforms - زي رسمي', 'description' => 'Work and school uniforms', 'is_dimension_based' => false],
                ['name' => 'Scarves - أوشحة', 'description' => 'Scarves and head coverings', 'is_dimension_based' => false],
            ]);
        }
        
        // --- Laundry Special Items Category ---
        $specialItemsCat = $findCat('Special Items');
        if ($specialItemsCat) {
            $this->createTypesForCategory($specialItemsCat, [
                ['name' => 'Wedding Dresses - فساتين زفاف', 'description' => 'Delicate wedding dresses requiring special care', 'is_dimension_based' => false],
                ['name' => 'Evening Gowns - فساتين سهرة', 'description' => 'Formal evening wear', 'is_dimension_based' => false],
                ['name' => 'Leather Jackets - جواكت جلد', 'description' => 'Leather outerwear requiring special treatment', 'is_dimension_based' => false],
                ['name' => 'Silk Clothing - ملابس حرير', 'description' => 'Delicate silk garments', 'is_dimension_based' => false],
                ['name' => 'Blazers - بليزر', 'description' => 'Formal blazers', 'is_dimension_based' => false],
                ['name' => 'Traditional Attire - ملابس تقليدية', 'description' => 'Traditional cultural clothing', 'is_dimension_based' => false],
            ]);
        }

        // --- Laundry Household Items Category ---
        $householdItemsCat = $findCat('Household Items');
        if ($householdItemsCat) {
            $this->createTypesForCategory($householdItemsCat, [
                ['name' => 'Bed Sheets - شراشف سرير', 'description' => 'Bed sheets and covers', 'is_dimension_based' => false],
                ['name' => 'Comforters / Duvets - لحاف وملاءات', 'description' => 'Bed comforters and duvets', 'is_dimension_based' => false],
                ['name' => 'Pillow Covers - أغطية وسادات', 'description' => 'Pillow cases and covers', 'is_dimension_based' => false],
                ['name' => 'Curtains - ستائر', 'description' => 'Window curtains and drapes', 'is_dimension_based' => false],
                ['name' => 'Sofa Covers - أغطية أريكة', 'description' => 'Sofa and furniture covers', 'is_dimension_based' => false],
                ['name' => 'Blankets - بطانيات', 'description' => 'Bed blankets and throws', 'is_dimension_based' => false],
                ['name' => 'Mattress Covers - أغطية فراش', 'description' => 'Mattress protectors and covers', 'is_dimension_based' => false],
                ['name' => 'Tablecloths - مفارش طاولة', 'description' => 'Table covers and cloths', 'is_dimension_based' => false],
                ['name' => 'Towels - مناشف', 'description' => 'Bath and hand towels', 'is_dimension_based' => false],
            ]);
        }

        // --- Laundry Carpets & Rugs Category ---
        $carpetsRugsCat = $findCat('Carpets & Rugs');
        if ($carpetsRugsCat) {
            $this->createTypesForCategory($carpetsRugsCat, [
                ['name' => 'Small Rugs - سجاد صغير', 'description' => 'Small area rugs (calculated by length × width)', 'is_dimension_based' => true],
                ['name' => 'Medium Carpets - سجاد متوسط', 'description' => 'Medium sized carpets (calculated by length × width)', 'is_dimension_based' => true],
                ['name' => 'Large Area Carpets - سجاد كبير', 'description' => 'Large area carpets (calculated by length × width)', 'is_dimension_based' => true],
                ['name' => 'Prayer Rugs - سجاد صلاة', 'description' => 'Prayer mats and rugs (calculated by length × width)', 'is_dimension_based' => true],
            ]);
        }

        // --- Laundry Accessories Category ---
        $accessoriesCat = $findCat('Accessories');
        if ($accessoriesCat) {
            $this->createTypesForCategory($accessoriesCat, [
                ['name' => 'Socks - جوارب', 'description' => 'Socks and stockings', 'is_dimension_based' => false],
                ['name' => 'Gloves - قفازات', 'description' => 'Hand gloves', 'is_dimension_based' => false],
                ['name' => 'Hats / Caps - قبعات', 'description' => 'Hats and caps', 'is_dimension_based' => false],
                ['name' => 'Ties - ربطات عنق', 'description' => 'Neck ties', 'is_dimension_based' => false],
            ]);
        }

        $this->command->info('Laundry product types seeded successfully.');
    }

    /**
     * A helper function to create multiple product types for a given category.
     */
    private function createTypesForCategory(ProductCategory $category, array $types): void
    {
        foreach ($types as $type) {
            ProductType::firstOrCreate(
                ['name' => $type['name'], 'product_category_id' => $category->id],
                $type + ['product_category_id' => $category->id]
            );
        }
    }
} 