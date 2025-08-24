<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductComposition;

class ProductCompositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding product compositions...');

        $compositions = [
            // Proteins - بروتينات
            'لحم بقري',
            'دجاج',
            'لحم غنم',
            'سمك',
            'جمبري',
            'برجر',
            'كباب',
            'كبدة',
            'تكة',
            'فلافل',

            // Dairy - منتجات الألبان
            'جبن',
            'جبنة بيضاء',
            'جبنة شيدر',
            'جبنة موزاريلا',
            'زبدة',
            'كريمة',
            'لبن',

            // Vegetables - خضروات
            'طماطم',
            'خس',
            'بصل',
            'خيار',
            'جزر',
            'فلفل',
            'فلفل حار',
            'زيتون',
            'مخلل',
            'بطاطس',
            'بطاطس مقلية',

            // Sauces & Condiments - صلصات وتوابل
            'مايونيز',
            'كاتشب',
            'خردل',
            'صلصة ثوم',
            'صلصة حارة',
            'صلصة تارتار',
            'صلصة رانش',
            'صلصة باربيكيو',
            'صلصة سيزر',
            'صلصة ألفريدو',
            'صلصة بيستو',

            // Grains & Breads - حبوب وخبز
            'أرز',
            'خبز',
            'خبز صاج',
            'خبز برجر',
            'خبز ساندويتش',
            'خبز بيتا',
            'خبز توست',
            'معكرونة',

            // Salads & Sides - سلطات وأطباق جانبية
            'سلطة',
            'سلطة خضراء',
            'سلطة سيزر',
            'سلطة يونانية',
            'سلطة كول سلو',
            'سلطة بطاطس',
            'سلطة أرز',

            // Drinks & Beverages - مشروبات
            'ماء',
            'عصير برتقال',
            'عصير تفاح',
            'عصير مانجو',
            'عصير فراولة',
            'ليمونادة',
            'شاي',
            'قهوة',
            'كولا',
            'سبرايت',
            'فانتا',

            // Desserts - حلويات
            'آيس كريم',
            'كيك',
            'بسكويت',
            'كنافة',
            'بقلاوة',
            'حلاوة',

            // Spices & Herbs - توابل وأعشاب
            'ملح',
            'فلفل أسود',
            'كمون',
            'كركم',
            'زعتر',
            'بقدونس',
            'كزبرة',
            'نعناع',
            'ريحان',
            'أوريغانو',
        ];

        $created = 0;
        foreach ($compositions as $compositionName) {
            $createdComposition = ProductComposition::firstOrCreate(
                ['name' => $compositionName],
                ['name' => $compositionName]
            );
            
            if ($createdComposition->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("Product compositions seeded successfully. Created {$created} new compositions.");
    }
}
