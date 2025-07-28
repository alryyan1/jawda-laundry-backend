<?php

namespace Database\Seeders\Laundry;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class LaundryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds for laundry categories.
     */
    public function run(): void
    {
        $this->command->info('Seeding laundry categories...');

        $categories = [
            [
                'name' => 'Clothes - ملابس غسيل',
                'description' => 'Everyday wear that follows standard pricing per piece. ملابس يومية تتبع تسعير قياسي لكل قطعة.'
            ],
            [
                'name' => 'Special Items - قطع غسيل خاصة',
                'description' => 'Delicate or expensive items that need special care. قطع حساسة أو باهظة الثمن تحتاج عناية خاصة.'
            ],
            [
                'name' => 'Household Items - مستلزمات غسيل منزلية',
                'description' => 'Usually larger items, often charged based on size or per piece. عادة قطع أكبر، غالباً ما يتم تسعيرها حسب الحجم أو لكل قطعة.'
            ],
            [
                'name' => 'Carpets & Rugs - سجاد وبسط غسيل',
                'description' => 'These are usually charged by area (e.g., per square meter/foot). عادة ما يتم تسعيرها حسب المساحة (مثل المتر المربع/القدم).'
            ],
            [
                'name' => 'Accessories - إكسسوارات غسيل',
                'description' => 'Small items, often charged at a lower rate. قطع صغيرة، غالباً ما يتم تسعيرها بمعدل أقل.'
            ]
        ];

        foreach ($categories as $category) {
            ProductCategory::firstOrCreate(
                ['name' => $category['name']], 
                $category
            );
        }
        
        $this->command->info('Laundry categories seeded successfully.');
    }
} 