<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductType;
use App\Models\ProductCategory;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds for product types.
     */
    public function run(): void
    {
        $this->command->info('Seeding product types...');
        
        // Helper function to find a category by its English name part
        $findCat = fn($en_name) => ProductCategory::where('name', 'LIKE', $en_name . '%')->first();

        // --- Hot Drinks ---
        $hotDrinksCat = $findCat('Hot Drinks');
        if ($hotDrinksCat) {
            $this->createTypesForCategory($hotDrinksCat, [
                ['name' => 'Khedri Tea (Istikan) - شاي خدري استكانة', 'is_dimension_based' => false],
                ['name' => 'Khedri Tea - شاي خدري', 'is_dimension_based' => false],
                ['name' => 'Karak Tea - شاي كرك', 'is_dimension_based' => false],
                ['name' => 'Green Tea - شاي اخضر', 'is_dimension_based' => false],
                ['name' => 'Thyme Tea - شاي زعتر', 'is_dimension_based' => false],
                ['name' => 'Cinnamon Tea - شاي قرفة', 'is_dimension_based' => false],
                ['name' => 'Nescafe - نسكافيه', 'is_dimension_based' => false],
                ['name' => 'Cappuccino - كابتشينو', 'is_dimension_based' => false],
                ['name' => 'Omani Coffee - قهوة عمانية', 'is_dimension_based' => false],
                ['name' => 'Black Coffee - قهوة سوداء', 'is_dimension_based' => false],
            ]);
        }
        
        // --- Specialty Coffee ---
        $specialtyCoffeeCat = $findCat('Specialty Coffee');
        if ($specialtyCoffeeCat) {
            $this->createTypesForCategory($specialtyCoffeeCat, [
                ['name' => 'Espresso - اسبريسو', 'is_dimension_based' => false],
                ['name' => 'Americano - أمريكانو', 'is_dimension_based' => false],
            ]);
        }

        // --- Breads & Pastries ---
        $pancakesCat = $findCat('Salalah Pancakes');
        if ($pancakesCat) {
            $this->createTypesForCategory($pancakesCat, $this->getFillings());
        }
        $hababCat = $findCat('Al-Habab Bread');
        if ($hababCat) {
            $this->createTypesForCategory($hababCat, $this->getFillings(true));
        }
        $croissantCat = $findCat('Croissant');
        if ($croissantCat) {
            $this->createTypesForCategory($croissantCat, $this->getFillings(true, true));
        }
        $parathaCat = $findCat('Paratha Bread');
        if ($parathaCat) {
            $this->createTypesForCategory($parathaCat, $this->getFillings(true, true));
        }
        $chapatiCat = $findCat('Chapati Bread');
        if ($chapatiCat) {
            $this->createTypesForCategory($chapatiCat, $this->getFillings(true, true, true));
        }
        $toastCat = $findCat('Toast');
        if ($toastCat) {
            $this->createTypesForCategory($toastCat, $this->getFillings(true, true, true, true));
        }

        // --- Pastries ---
        $pastriesCat = $findCat('Pastries');
        if ($pastriesCat) {
            $this->createTypesForCategory($pastriesCat, [
                ['name' => 'Luqaimat (Toppings) - لقيمات (إضافات)', 'description' => 'Toppings: Honey, Date Syrup, Condensed Milk, Nutella', 'is_dimension_based' => false],
                ['name' => 'Plain Mandazi - مندازي سادة', 'is_dimension_based' => false],
                ['name' => 'Mandazi with Cheese - مندازي جبن', 'is_dimension_based' => false],
                ['name' => 'Mandazi with Cheese & Honey - مندازي جبن وعسل', 'is_dimension_based' => false],
                ['name' => 'Mandazi with Cheese & Oman Chips - مندازي جبن وبطاطس عمان', 'is_dimension_based' => false],
                ['name' => 'Mandazi with Nutella - مندازي نوتيلا', 'is_dimension_based' => false],
            ]);
        }

        // --- Other Meals ---
        $otherMealsCat = $findCat('Other Meals');
        if ($otherMealsCat) {
            $this->createTypesForCategory($otherMealsCat, [
                ['name' => 'Khedri Breakfast - فطور خدري', 'description' => 'Egg Tomato or Shakshuka, Nakhi, Chebab, Balaleet, Salad, Paratha, Istikan Tea', 'is_dimension_based' => false],
                ['name' => 'Khedri Fwala - فوالة خدري', 'description' => 'Luqaimat, Nakhi, Chebab, Mandazi, Balaleet', 'is_dimension_based' => false],
                ['name' => 'Qurs Mufattat - قرص مفتت', 'is_dimension_based' => false],
                ['name' => 'Balaleet / Siwaya - بلاليط / سيويا', 'is_dimension_based' => false],
                ['name' => 'Nakhi - نخي', 'is_dimension_based' => false],
                ['name' => 'Shakshuka with Bread & Salad - شكشوكة مع الخبز والسلطة', 'is_dimension_based' => false],
                ['name' => 'Egg Tomato with Bread & Salad - بيض طماط مع الخبز والسلطة', 'is_dimension_based' => false],
                ['name' => 'Cheesecake with Toppings - تشيزكيك مع التغطية', 'is_dimension_based' => false],
                ['name' => 'Mixed Cheese with Bread and Salad - بيض طماط مع الخبز والسلطة', 'is_dimension_based' => false], // The Arabic name seems to be a typo on the menu, I used the English one
            ]);
        }

        $this->command->info('Product types seeded successfully.');
    }

    /**
     * A helper function to create multiple product types for a given category.
     */
    private function createTypesForCategory(ProductCategory $category, array $types): void
    {
        foreach ($types as $type) {
            ProductType::firstOrCreate(
                ['name' => explode(' - ', $type['name'])[0], 'product_category_id' => $category->id],
                $type + ['product_category_id' => $category->id] // Ensure category_id is set
            );
        }
    }

    /**
     * A helper function to get common fillings for different bread types.
     */
    private function getFillings(bool $withOmanChips = false, bool $withEgg = false, bool $withSausage = false, bool $withNutellaCheese = false): array
    {
        $fillings = [
            ['name' => 'Cheese - جبن', 'is_dimension_based' => false],
            ['name' => 'Cheese & Honey - جبن و عسل', 'is_dimension_based' => false],
            ['name' => 'Cheese & Date Syrup - جبن و دبس', 'is_dimension_based' => false],
            ['name' => 'Nutella - نوتيلا', 'is_dimension_based' => false],
        ];

        if ($withOmanChips) {
            $fillings[] = ['name' => 'Cheese & Oman Chips - جبن وبطاطس عمان', 'is_dimension_based' => false];
        }
        if ($withEgg) {
            $fillings[] = ['name' => 'Egg & Cheese - بيض و جبن', 'is_dimension_based' => false];
            $fillings[] = ['name' => 'Egg, Cheese & Oman Chips - جبن وبيض وبطاطس عمان', 'is_dimension_based' => false];
        }
        if ($withSausage) {
            $fillings[] = ['name' => 'Cheese & Sausage - جبن نقانق', 'is_dimension_based' => false];
        }
        if ($withNutellaCheese) {
             $fillings[] = ['name' => 'Cheese & Nutella - جبن نوتيلا', 'is_dimension_based' => false];
        }

        return $fillings;
    }
}