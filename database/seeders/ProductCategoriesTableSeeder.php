<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductCategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('product_categories')->delete();
        
        \DB::table('product_categories')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Clothes - ملابس غسيل',
                'description' => 'Everyday wear that follows standard pricing per piece. ملابس يومية تتبع تسعير قياسي لكل قطعة.',
                'image_url' => 'product-categories/1754578498_1753636919_tie.png',
                'sequence_prefix' => NULL,
                'sequence_enabled' => 0,
                'current_sequence' => 0,
                'created_at' => '2025-08-07 14:42:58',
                'updated_at' => '2025-08-07 14:54:58',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Special Items - قطع غسيل خاصة',
                'description' => 'Delicate or expensive items that need special care. قطع حساسة أو باهظة الثمن تحتاج عناية خاصة.',
                'image_url' => 'product-categories/1754578532_1754578466_1753637859_brand.png',
                'sequence_prefix' => NULL,
                'sequence_enabled' => 0,
                'current_sequence' => 0,
                'created_at' => '2025-08-07 14:42:58',
                'updated_at' => '2025-08-07 14:55:32',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Household Items - مستلزمات غسيل منزلية',
                'description' => 'Usually larger items, often charged based on size or per piece. عادة قطع أكبر، غالباً ما يتم تسعيرها حسب الحجم أو لكل قطعة.',
                'image_url' => 'product-categories/1754578514_1753637957_curtains.png',
                'sequence_prefix' => NULL,
                'sequence_enabled' => 0,
                'current_sequence' => 0,
                'created_at' => '2025-08-07 14:42:58',
                'updated_at' => '2025-08-07 14:55:14',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Carpets & Rugs - سجاد وبسط غسيل',
            'description' => 'These are usually charged by area (e.g., per square meter/foot). عادة ما يتم تسعيرها حسب المساحة (مثل المتر المربع/القدم).',
                'image_url' => 'product-categories/1754578480_1753637876_adornment.png',
                'sequence_prefix' => 'Z',
                'sequence_enabled' => 1,
                'current_sequence' => 7030,
                'created_at' => '2025-08-07 14:42:58',
                'updated_at' => '2025-08-09 12:22:13',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Accessories - إكسسوارات غسيل',
                'description' => 'Small items, often charged at a lower rate. قطع صغيرة، غالباً ما يتم تسعيرها بمعدل أقل.',
                'image_url' => 'product-categories/1754578466_1753637859_brand.png',
                'sequence_prefix' => NULL,
                'sequence_enabled' => 0,
                'current_sequence' => 0,
                'created_at' => '2025-08-07 14:42:58',
                'updated_at' => '2025-08-07 14:54:27',
            ),
        ));
        
        
    }
}