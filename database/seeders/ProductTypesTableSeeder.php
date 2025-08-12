<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTypesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('product_types')->delete();
        
        DB::table('product_types')->insert(array (
            0 => 
            array (
                'id' => 54,
                'product_category_id' => 1,
                'name' => 'Dishdasha - دشداشه',
                'description' => NULL,
                'image_url' => 'product_types/675dffc8655ab.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            1 => 
            array (
                'id' => 55,
                'product_category_id' => 5,
                'name' => 'SOCKS جوارب',
                'description' => NULL,
                'image_url' => 'product_types/675dffc8677da.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            2 => 
            array (
                'id' => 56,
                'product_category_id' => 1,
                'name' => 'U T-SHIRT فانية داخلية',
                'description' => NULL,
                'image_url' => 'product_types/675dffc868533.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            3 => 
            array (
                'id' => 57,
                'product_category_id' => 5,
                'name' => 'CAP كاب',
                'description' => NULL,
                'image_url' => 'product_types/675dffc86927f.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            4 => 
            array (
                'id' => 58,
                'product_category_id' => 1,
                'name' => 'MEN SHIRT قميص رجالي',
                'description' => NULL,
                'image_url' => 'product_types/675dffc86aad5.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            5 => 
            array (
                'id' => 59,
                'product_category_id' => 1,
                'name' => 'SHORT شورت',
                'description' => NULL,
                'image_url' => 'product_types/675dffc86b631.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            6 => 
            array (
                'id' => 60,
                'product_category_id' => 5,
                'name' => 'kumma كمه',
                'description' => NULL,
                'image_url' => 'product_types/675dffc8700e1.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            7 => 
            array (
                'id' => 61,
                'product_category_id' => 2,
                'name' => 'PAKISTANI UNIFORM ملابس باكستان',
                'description' => NULL,
                'image_url' => 'product_types/675dffc870b06.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            8 => 
            array (
                'id' => 62,
                'product_category_id' => 2,
                'name' => 'MILITRY SHIRT قميص عسكري',
                'description' => NULL,
                'image_url' => 'product_types/675dffc871f46.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            9 => 
            array (
                'id' => 63,
                'product_category_id' => 1,
                'name' => 'JACKET جاكيت',
                'description' => NULL,
                'image_url' => 'product_types/675dffc876519.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            10 => 
            array (
                'id' => 64,
                'product_category_id' => 1,
                'name' => 'UNEDR WEAR ملابس داخلية',
                'description' => NULL,
                'image_url' => 'product_types/675dffc87ac4b.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            11 => 
            array (
                'id' => 65,
                'product_category_id' => 5,
                'name' => 'SHOES جوتي& نعال',
                'description' => NULL,
                'image_url' => 'product_types/675dffc87ceaf.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            12 => 
            array (
                'id' => 66,
                'product_category_id' => 1,
                'name' => 'MEN TROUSER بنطلون رجالي',
                'description' => NULL,
                'image_url' => 'product_types/675dffc87fe2f.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            13 => 
            array (
                'id' => 67,
                'product_category_id' => 1,
                'name' => 'WZAR وزار',
                'description' => NULL,
                'image_url' => 'product_types/675dffc885501.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            14 => 
            array (
                'id' => 68,
                'product_category_id' => 1,
                'name' => 'MUSAR OMAN مصر',
                'description' => NULL,
                'image_url' => 'product_types/675dffc8867cf.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            15 => 
            array (
                'id' => 69,
                'product_category_id' => 1,
                'name' => 'T.SHIRT فانيله',
                'description' => NULL,
                'image_url' => 'product_types/675dceb3c27be.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            16 => 
            array (
                'id' => 70,
                'product_category_id' => 1,
            'name' => 'عبائه رجاليه (بشت) Men bisht',
                'description' => NULL,
                'image_url' => 'product_types/676ff54b4dc89.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            17 => 
            array (
                'id' => 71,
                'product_category_id' => 1,
                'name' => 'Shirt - قميص',
                'description' => NULL,
                'image_url' => 'product_types/678181bbce1fe.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            18 => 
            array (
                'id' => 72,
                'product_category_id' => 1,
                'name' => 'afrol افرول',
                'description' => NULL,
                'image_url' => 'product_types/6781819fa4ad7.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            19 => 
            array (
                'id' => 73,
                'product_category_id' => 1,
            'name' => 'Sleepwear Pajamas - بيجامه نوم(دشاشه)',
                'description' => NULL,
                'image_url' => 'product_types/676ff499e9acd.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            20 => 
            array (
                'id' => 74,
                'product_category_id' => 1,
                'name' => 'Small Dihdash - دشداشة صغيرة',
                'description' => NULL,
                'image_url' => 'product_types/6781809a0c1c2.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            21 => 
            array (
                'id' => 75,
                'product_category_id' => 1,
                'name' => 'Small Trouser - بنطلون صغير',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 12:45:58',
            ),
            22 => 
            array (
                'id' => 76,
                'product_category_id' => 1,
                'name' => 'Small T-Shirt - تي شيرت صغير',
                'description' => NULL,
                'image_url' => 'product_types/678180165d4b4.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            23 => 
            array (
                'id' => 77,
                'product_category_id' => 1,
                'name' => 'Big Jacket - جاكيت كبير',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 12:45:58',
            ),
            24 => 
            array (
                'id' => 78,
                'product_category_id' => 2,
                'name' => 'ABAYA عبايه',
                'description' => NULL,
                'image_url' => 'product_types/675dffc869e45.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            25 => 
            array (
                'id' => 79,
                'product_category_id' => 2,
                'name' => 'LADIES DISHDASHA جلابيه',
                'description' => NULL,
                'image_url' => 'product_types/675dffc86d7d3.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            26 => 
            array (
                'id' => 80,
                'product_category_id' => 2,
                'name' => 'Skirt تنوره',
                'description' => NULL,
                'image_url' => 'product_types/675dffc86e4ab.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            27 => 
            array (
                'id' => 81,
                'product_category_id' => 2,
                'name' => 'Wedding Dress فستان سهره',
                'description' => NULL,
                'image_url' => 'product_types/675dffc86f68f.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            28 => 
            array (
                'id' => 82,
                'product_category_id' => 2,
                'name' => 'Wedding DRESS فستان فرح',
                'description' => NULL,
                'image_url' => 'product_types/676feeaba30fb.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            29 => 
            array (
                'id' => 83,
                'product_category_id' => 2,
                'name' => 'LADIES U-WEAR ملابس داخلية نسائية',
                'description' => NULL,
                'image_url' => 'product_types/675dffc873c97.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            30 => 
            array (
                'id' => 84,
                'product_category_id' => 2,
                'name' => 'LADIES TROUSER بنطلون نسائي',
                'description' => NULL,
                'image_url' => 'product_types/675dffc874b1d.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            31 => 
            array (
                'id' => 85,
                'product_category_id' => 2,
                'name' => 'LADIES SHIRT قميص نسائي',
                'description' => NULL,
                'image_url' => 'product_types/675dffc875a19.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            32 => 
            array (
                'id' => 86,
                'product_category_id' => 2,
                'name' => 'SHAILA شيله',
                'description' => NULL,
                'image_url' => 'product_types/675dffc879d46.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            33 => 
            array (
                'id' => 87,
                'product_category_id' => 5,
                'name' => 'Ladies Bag - حقيبة نسائية',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 12:45:58',
            ),
            34 => 
            array (
                'id' => 88,
                'product_category_id' => 2,
                'name' => 'قميص نوم نسائي  Women\'s nightgown',
                'description' => NULL,
                'image_url' => 'product_types/676fef2fd16ff.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            35 => 
            array (
                'id' => 89,
                'product_category_id' => 2,
                'name' => 'School Dress - فستان مدرسة',
                'description' => NULL,
                'image_url' => 'product_types/67817ffb036e1.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            36 => 
            array (
                'id' => 90,
                'product_category_id' => 2,
                'name' => 'School Uniform - زي مدرسة',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 12:45:58',
            ),
            37 => 
            array (
                'id' => 91,
                'product_category_id' => 3,
                'name' => 'TOWEL-D فوطه كبيره',
                'description' => NULL,
                'image_url' => 'product_types/675dffc876f78.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            38 => 
            array (
                'id' => 92,
                'product_category_id' => 3,
                'name' => 'TOWEL-S فوطه صغيره',
                'description' => NULL,
                'image_url' => 'product_types/675dffc87781a.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            39 => 
            array (
                'id' => 93,
                'product_category_id' => 3,
                'name' => 'PARDA ستاره',
                'description' => NULL,
                'image_url' => 'product_types/675dffc878218.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            40 => 
            array (
                'id' => 94,
                'product_category_id' => 3,
                'name' => 'BED SHEET-D شرشف كبير',
                'description' => NULL,
                'image_url' => 'product_types/675dffc878c0c.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            41 => 
            array (
                'id' => 95,
                'product_category_id' => 3,
                'name' => 'BED SHEET-S شرشف صغير',
                'description' => NULL,
                'image_url' => 'product_types/675dffc879481.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            42 => 
            array (
                'id' => 96,
                'product_category_id' => 3,
                'name' => 'PILLOW مخده',
                'description' => NULL,
                'image_url' => 'product_types/675dffc87e2dc.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            43 => 
            array (
                'id' => 97,
                'product_category_id' => 2,
                'name' => 'SHAYLA شيله',
                'description' => NULL,
                'image_url' => 'product_types/675dceb36f306.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            44 => 
            array (
                'id' => 98,
                'product_category_id' => 3,
                'name' => 'فوطه صغيره-TOWEL-S',
                'description' => NULL,
                'image_url' => 'product_types/676ff50969cc0.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            45 => 
            array (
                'id' => 99,
                'product_category_id' => 3,
                'name' => 'فوطه كبيره-TOWEL-D',
                'description' => NULL,
                'image_url' => 'product_types/676ff50385b0b.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            46 => 
            array (
                'id' => 100,
                'product_category_id' => 3,
                'name' => 'برنوص صغير-SMALL BLANKET',
                'description' => NULL,
                'image_url' => 'product_types/678180f8b542e.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            47 => 
            array (
                'id' => 101,
                'product_category_id' => 3,
                'name' => 'BIG BLANKET_ برنوص كبير',
                'description' => NULL,
                'image_url' => 'product_types/678180d00a646.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            48 => 
            array (
                'id' => 102,
                'product_category_id' => 3,
                'name' => 'Table Cover - وجه طاولة',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 12:45:58',
            ),
            49 => 
            array (
                'id' => 103,
                'product_category_id' => 3,
                'name' => 'Small Parda - ستارة صغيرة',
                'description' => NULL,
                'image_url' => NULL,
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 12:45:58',
            ),
            50 => 
            array (
                'id' => 104,
                'product_category_id' => 2,
                'name' => 'MILITRY PANTS بنطلون عسكري',
                'description' => NULL,
                'image_url' => 'product_types/675dffc8715a6.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            51 => 
            array (
                'id' => 105,
                'product_category_id' => 2,
                'name' => 'MILITARY UNIFORM ملابس عسكرية',
                'description' => NULL,
                'image_url' => 'product_types/675dffc872954.png',
                'is_dimension_based' => 0,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
            52 => 
            array (
                'id' => 106,
                'product_category_id' => 4,
                'name' => 'zoolia -زوليه',
                'description' => NULL,
                'image_url' => 'product_types/678179cfee4e0.png',
                'is_dimension_based' => 1,
                'created_at' => '2025-08-07 12:45:58',
                'updated_at' => '2025-08-07 15:01:39',
            ),
        ));
        
        
    }
}