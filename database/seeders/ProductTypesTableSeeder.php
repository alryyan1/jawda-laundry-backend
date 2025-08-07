<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('product_types')->delete();
        
        \DB::table('product_types')->insert(array (
            0 => 
            array (
                'id' => 54,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'Dishdasha - دشداشه',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            1 => 
            array (
                'id' => 55,
                'product_category_id' => 5, // Accessories - إكسسوارات غسيل
                'name' => 'SOCKS جوارب',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            2 => 
            array (
                'id' => 56,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'U T-SHIRT فانية داخلية',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            3 => 
            array (
                'id' => 57,
                'product_category_id' => 5, // Accessories - إكسسوارات غسيل
                'name' => 'CAP كاب',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            4 => 
            array (
                'id' => 58,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'MEN SHIRT قميص رجالي',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            5 => 
            array (
                'id' => 59,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'SHORT شورت',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            6 => 
            array (
                'id' => 60,
                'product_category_id' => 5, // Accessories - إكسسوارات غسيل
                'name' => 'kumma كمه',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            7 => 
            array (
                'id' => 61,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'PAKISTANI UNIFORM ملابس باكستان',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            8 => 
            array (
                'id' => 62,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'MILITRY SHIRT قميص عسكري',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            9 => 
            array (
                'id' => 63,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'JACKET جاكيت',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            10 => 
            array (
                'id' => 64,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'UNEDR WEAR ملابس داخلية',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            11 => 
            array (
                'id' => 65,
                'product_category_id' => 5, // Accessories - إكسسوارات غسيل
                'name' => 'SHOES جوتي& نعال',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            12 => 
            array (
                'id' => 66,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'MEN TROUSER بنطلون رجالي',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            13 => 
            array (
                'id' => 67,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'WZAR وزار',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            14 => 
            array (
                'id' => 68,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'MUSAR OMAN مصر',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            15 => 
            array (
                'id' => 69,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'T.SHIRT فانيله',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            16 => 
            array (
                'id' => 70,
                'product_category_id' => 1, // Clothes - ملابس غسيل
            'name' => 'عبائه رجاليه (بشت) Men bisht',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            17 => 
            array (
                'id' => 71,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'Shirt - قميص',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            18 => 
            array (
                'id' => 72,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'afrol افرول',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            19 => 
            array (
                'id' => 73,
                'product_category_id' => 1, // Clothes - ملابس غسيل
            'name' => 'Sleepwear Pajamas - بيجامه نوم(دشاشه)',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:49:30',
            ),
            20 => 
            array (
                'id' => 74,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'Small Dihdash - دشداشة صغيرة',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            21 => 
            array (
                'id' => 75,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'Small Trouser - بنطلون صغير',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            22 => 
            array (
                'id' => 76,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'Small T-Shirt - تي شيرت صغير',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            23 => 
            array (
                'id' => 77,
                'product_category_id' => 1, // Clothes - ملابس غسيل
                'name' => 'Big Jacket - جاكيت كبير',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            24 => 
            array (
                'id' => 78,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'ABAYA عبايه',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            25 => 
            array (
                'id' => 79,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'LADIES DISHDASHA جلابيه',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            26 => 
            array (
                'id' => 80,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'Skirt تنوره',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            27 => 
            array (
                'id' => 81,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'Wedding Dress فستان سهره',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            28 => 
            array (
                'id' => 82,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'Wedding DRESS فستان فرح',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            29 => 
            array (
                'id' => 83,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'LADIES U-WEAR ملابس داخلية نسائية',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            30 => 
            array (
                'id' => 84,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'LADIES TROUSER بنطلون نسائي',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            31 => 
            array (
                'id' => 85,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'LADIES SHIRT قميص نسائي',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            32 => 
            array (
                'id' => 86,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'SHAILA شيله',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            33 => 
            array (
                'id' => 87,
                'product_category_id' => 5, // Accessories - إكسسوارات غسيل
                'name' => 'Ladies Bag - حقيبة نسائية',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            34 => 
            array (
                'id' => 88,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'قميص نوم نسائي  Women\'s nightgown',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            35 => 
            array (
                'id' => 89,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'School Dress - فستان مدرسة',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            36 => 
            array (
                'id' => 90,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'School Uniform - زي مدرسة',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            37 => 
            array (
                'id' => 91,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'TOWEL-D فوطه كبيره',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            38 => 
            array (
                'id' => 92,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'TOWEL-S فوطه صغيره',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            39 => 
            array (
                'id' => 93,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'PARDA ستاره',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            40 => 
            array (
                'id' => 94,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'BED SHEET-D شرشف كبير',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            41 => 
            array (
                'id' => 95,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'BED SHEET-S شرشف صغير',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            42 => 
            array (
                'id' => 96,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'PILLOW مخده',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            43 => 
            array (
                'id' => 97,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'SHAYLA شيله',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            44 => 
            array (
                'id' => 98,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'فوطه صغيره-TOWEL-S',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            45 => 
            array (
                'id' => 99,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'فوطه كبيره-TOWEL-D',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            46 => 
            array (
                'id' => 100,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'برنوص صغير-SMALL BLANKET',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            47 => 
            array (
                'id' => 101,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'BIG BLANKET_ برنوص كبير',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            48 => 
            array (
                'id' => 102,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'Table Cover - وجه طاولة',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            49 => 
            array (
                'id' => 103,
                'product_category_id' => 3, // Household Items - مستلزمات غسيل منزلية
                'name' => 'Small Parda - ستارة صغيرة',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            50 => 
            array (
                'id' => 104,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'MILITRY PANTS بنطلون عسكري',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            51 => 
            array (
                'id' => 105,
                'product_category_id' => 2, // Special Items - قطع غسيل خاصة
                'name' => 'MILITARY UNIFORM ملابس عسكرية',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
            52 => 
            array (
                'id' => 106,
                'product_category_id' => 4, // Carpets & Rugs - سجاد وبسط غسيل
                'name' => 'zoolia -زوليه',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 1, // Dimension-based for carpets
                'created_at' => '2025-08-07 16:45:58',
                'updated_at' => '2025-08-07 16:45:58',
            ),
        ));
        
        
    }
}